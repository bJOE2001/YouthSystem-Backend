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
        $user = auth()->user();
        
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
            return response()->json(['message' => 'You have already completed this examination.'], 403);
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
                'started_at' => $examination->started_at,
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
    public function submit(Request $request, $id)
    {
        $user = auth()->user();
        
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

            foreach ($payload as $ans) {
                $qId = $ans['question_id'] ?? null;
                if (!$qId || !isset($questions[$qId])) continue;
                
                $question = $questions[$qId];
                $pointsForQuestion = $question->points ?? 1;
                

                $type = $question->type;
                $isCorrect = false;

                if ($type === 'essay') {
                    $hasEssay = true;
                    EcesproApplicantExamAnswer::create([
                        'ecespro_examination_id' => $examination->id,
                        'question_id' => $qId,
                        'answer_text' => $ans['answer_text'] ?? null,
                        'is_correct' => false // Set to false instead of null since DB column is not nullable
                    ]);
                } 
                else if ($type === 'fill_in_blank') {
                    $correctChoice = $question->choices->firstWhere('is_correct', true);
                    $userAnswer = strtolower(trim($ans['answer_text'] ?? ''));
                    $correctAnswer = strtolower(trim($correctChoice ? $correctChoice->choice_text : ''));
                    
                    if ($userAnswer === $correctAnswer && !empty($correctAnswer)) {
                        $isCorrect = true;
                        $earnedPoints += $pointsForQuestion;
                    }

                    EcesproApplicantExamAnswer::create([
                        'ecespro_examination_id' => $examination->id,
                        'question_id' => $qId,
                        'answer_text' => $ans['answer_text'] ?? null,
                        'is_correct' => $isCorrect
                    ]);
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
                        EcesproApplicantExamAnswer::create([
                            'ecespro_examination_id' => $examination->id,
                            'question_id' => $qId,
                            'answer_choice_id' => null,
                            'is_correct' => false
                        ]);
                    } else {
                        foreach ($selectedIds as $sId) {
                            EcesproApplicantExamAnswer::create([
                                'ecespro_examination_id' => $examination->id,
                                'question_id' => $qId,
                                'answer_choice_id' => $sId,
                                'is_correct' => $isCorrect
                            ]);
                        }
                    }
                }
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
                'score' => $earnedPoints . '/' . $totalPoints,
                'status' => $status,
                'completed_at' => now(),
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






