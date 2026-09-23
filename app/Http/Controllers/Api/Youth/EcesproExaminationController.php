<?php

namespace App\Http\Controllers\Api\Youth;

use App\Http\Controllers\Controller;
use App\Models\EcesproExamination;
use App\Models\EcesproApplicantExamAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcesproExaminationController extends Controller
{
    /**
     * Start the examination and return questions.
     */
    public function start(Request $request, $id)
    {
        $user = $request->user();
        
        $examination = EcesproExamination::with([
            'application',
            'batch',
            'application.program.examinationSetup.questionnaire.questions.choices'
        ])->findOrFail($id);

        // Verify ownership
        Log::info('Ownership check: App User ' . $examination->application->user_id . ' vs Auth User ' . $user->id);
        if ($examination->application->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized access to this examination.'], 403);
        }

        // Verify exam is enabled for the batch
        if (!$examination->batch || !$examination->batch->is_exam_enabled) {
            return response()->json(['message' => 'The online examination is not currently enabled for your batch.'], 403);
        }

        // Verify setup exists
        $setup = $examination->application->program->examinationSetup;
        if (!$setup || !$setup->questionnaire) {
            return response()->json(['message' => 'Examination setup is missing.'], 404);
        }

        // Check if already completed
        if ($examination->status != 'Pending' && $examination->status != 'In Progress') {
            return response()->json([
                'message' => 'You have already completed this examination.',
                'attempts_used' => $examination->attempts_used,
                'attempts_allowed' => $setup->attempts_allowed,
                'can_retake' => $examination->attempts_used < $setup->attempts_allowed
            ], 403);
        }

        // Mark as started if not yet
        if (!$examination->started_at) {
            $examination->update([
                'started_at' => now(),
                'status' => 'In Progress'
            ]);
        }

        // Format questions (hide correct answers!)
        $questions = $setup->questionnaire->questions->map(function ($q) {
            return [
                'id' => $q->id,
                'type' => $q->type,
                'question_text' => $q->question_text,
                'points' => $q->points,
                'image_path' => $q->image_path,
                'allow_multiple_answers' => $q->allow_multiple_answers,
                'max_answers' => $q->choices->where('is_correct', true)->count(),
                'choices' => $q->choices->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'choice_text' => $c->choice_text,
                    ];
                })
            ];
        });

        // If shuffle is enabled
        if ($setup->shuffle_questions) {
            $questions = $questions->shuffle()->values();
        }

        return response()->json([
            'examination' => [
                'id' => $examination->id,
                'started_at' => $examination->started_at ? \Carbon\Carbon::parse($examination->started_at)->toIso8601String() : null,
                'time_extension_minutes' => $examination->time_extension_minutes,
            ],
            'setup' => [
                'time_limit_minutes' => $setup->time_limit_minutes,
            ],
            'questionnaire' => [
                'title' => $setup->questionnaire->title,
                'description' => $setup->questionnaire->description,
                'questions' => $questions,
            ]
        ]);
    }

    /**
     * Submit answers and auto-grade.
     */
    public function retake(Request $request, $id)
    {
        $user = $request->user();
        
        $examination = EcesproExamination::with([
            'application.program.examinationSetup'
        ])->findOrFail($id);

        if ($examination->application->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $setup = $examination->application->program->examinationSetup;
        if ($examination->attempts_used >= $setup->attempts_allowed) {
            return response()->json(['message' => 'You have consumed all your attempts.'], 403);
        }

        DB::beginTransaction();
        try {
            EcesproApplicantExamAnswer::where('ecespro_examination_id', $examination->id)->delete();

            $examination->update([
                'status' => 'Pending',
                'score' => null,
                'started_at' => null,
                'completed_at' => null,
            ]);

            DB::commit();
            return response()->json(['message' => 'Exam reset successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Retake exam error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retake exam.'], 500);
        }
    }

    public function submit(Request $request, $id)
    {
        $user = $request->user();
        
        $examination = EcesproExamination::with([
            'application.program.examinationSetup.questionnaire.questions.choices'
        ])->findOrFail($id);

        Log::info('Ownership check: App User ' . $examination->application->user_id . ' vs Auth User ' . $user->id);
        if ($examination->application->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($examination->status === 'Passed' || $examination->status === 'Failed') {
            return response()->json(['message' => 'Examination already submitted.'], 400);
        }

        $setup = $examination->application->program->examinationSetup;
        $questions = $setup->questionnaire->questions->keyBy('id');
        
        $payload = $request->input('answers', $request->all()); 
        
        $totalPoints = $questions->sum('points');
        $earnedPoints = 0;
        $hasEssay = $questions->where('type', 'essay')->count() > 0;

        DB::beginTransaction();

        try {
            // Delete previous answers just in case of multiple submissions during 'In Progress'
            EcesproApplicantExamAnswer::where('ecespro_examination_id', $examination->id)->delete();

            $insertData = [];
            $now = now();

            foreach ($payload as $ans) {
                $qId = $ans['question_id'] ?? null;
                if (!$qId || !isset($questions[$qId])) continue;
                
                $question = $questions[$qId];
                $pointsForQuestion = $question->points ?? 1;
                

                $type = $question->type;
                $isCorrect = false;

                if ($type === 'essay') {
                    $hasEssay = true;
                    $insertData[] = [
                        'ecespro_examination_id' => $examination->id,
                        'question_id' => $qId,
                        'answer_choice_id' => null,
                        'answer_text' => $ans['answer_text'] ?? null,
                        'is_correct' => false,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                } 
                else if ($type === 'multiple_choice' || $type === 'true_false') {
                    $selectedIds = $ans['selected_choices'] ?? [];
                    if (!is_array($selectedIds)) {
                        $selectedIds = [$selectedIds];
                    }

                    $correctChoiceIds = $question->choices->where('is_correct', true)->pluck('id')->toArray();
                    
                    sort($selectedIds);
                    sort($correctChoiceIds);
                    
                    if (!empty($selectedIds) && $selectedIds == $correctChoiceIds) {
                        $isCorrect = true;
                        $earnedPoints += $pointsForQuestion;
                    }

                    if (empty($selectedIds)) {
                        $insertData[] = [
                            'ecespro_examination_id' => $examination->id,
                            'question_id' => $qId,
                            'answer_choice_id' => null,
                            'answer_text' => null,
                            'is_correct' => false,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    } else {
                        foreach ($selectedIds as $sId) {
                            $insertData[] = [
                                'ecespro_examination_id' => $examination->id,
                                'question_id' => $qId,
                                'answer_choice_id' => $sId,
                                'answer_text' => null,
                                'is_correct' => $isCorrect,
                                'created_at' => $now,
                                'updated_at' => $now
                            ];
                        }
                    }
                }
                else if ($type === 'fill_in_blank') {
                    $userAnswerText = strtolower(trim($ans['answer_text'] ?? ''));
                    $correctAnswerText = strtolower(trim($question->correct_answer_text ?? ''));
                    
                    if ($userAnswerText !== '' && $userAnswerText === $correctAnswerText) {
                        $isCorrect = true;
                        $earnedPoints += $pointsForQuestion;
                    }

                    $insertData[] = [
                        'ecespro_examination_id' => $examination->id,
                        'question_id' => $qId,
                        'answer_choice_id' => null,
                        'answer_text' => $ans['answer_text'] ?? null,
                        'is_correct' => $isCorrect,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
                else if ($type === 'modified_true_false') {
                    $selectedIds = $ans['selected_choices'] ?? [];
                    if (!is_array($selectedIds)) {
                        $selectedIds = [$selectedIds];
                    }

                    $selectedChoiceId = !empty($selectedIds) ? $selectedIds[0] : null;
                    $correctChoice = $question->choices->where('is_correct', true)->first();

                    if ($correctChoice && $selectedChoiceId == $correctChoice->id) {
                        if (strtolower(trim($correctChoice->choice_text)) === 'false') {
                            $userAnswerText = strtolower(trim($ans['answer_text'] ?? ''));
                            $correctAnswerText = strtolower(trim($question->correct_answer_text ?? ''));
                            if ($userAnswerText !== '' && $userAnswerText === $correctAnswerText) {
                                $isCorrect = true;
                                $earnedPoints += $pointsForQuestion;
                            }
                        } else {
                            $isCorrect = true;
                            $earnedPoints += $pointsForQuestion;
                        }
                    }

                    $insertData[] = [
                        'ecespro_examination_id' => $examination->id,
                        'question_id' => $qId,
                        'answer_choice_id' => $selectedChoiceId,
                        'answer_text' => $ans['answer_text'] ?? null,
                        'is_correct' => $isCorrect,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }

            if (!empty($insertData)) {
                EcesproApplicantExamAnswer::insert($insertData);
            }

            // Calculate status
            $passingPercentage = $setup->passing_percentage ?? 75;
            $scorePercentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
            
            $status = 'Pending';
            // If it has an essay, leave it as Pending for manual grading
            if (!$hasEssay) {
                $status = $scorePercentage >= $passingPercentage ? 'Passed' : 'Failed';
            }

            $examination->update([
                // Use slash format only when no essays (final score is known).
                // When essays exist, keep raw number so essay grading can increment it.
                'score' => $hasEssay ? $earnedPoints : $earnedPoints . '/' . $totalPoints,
                'status' => $status,
                'completed_at' => $hasEssay ? null : now(),
                'attempts_used' => $examination->attempts_used + 1,
            ]);
            
            if ($status === 'Passed') {
                $examination->application()->update([
                    'application_status' => 'Qualified for Interview',
                ]);
            } else if ($status === 'Failed') {
                $examination->application()->update([
                    'application_status' => 'Failed Exam',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Examination submitted successfully.',
                'score' => $earnedPoints,
                'total_points' => $totalPoints,
                'status' => $status,
                'has_essay' => $hasEssay
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Youth exam submit error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to submit examination.'], 500);
        }
    }
}









