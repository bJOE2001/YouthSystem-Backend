<?php
namespace App\Http\Controllers\Api\Youth;

use App\Http\Controllers\Controller;
use App\Models\EcesproExamination;
use App\Models\EcesproExaminationSetup;
use App\Models\EcesproApplicantExamAnswer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EcesproApplicantExamController extends Controller
{
    public function start(Request $request, $examination_id)
    {
        $exam = EcesproExamination::with('application.program.examinationSetup')->findOrFail($examination_id);
        
        if ($exam->application->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($exam->status !== 'Pending' && $exam->status !== 'Scheduled') { // Assuming it could be Scheduled
            return response()->json(['message' => 'Exam already taken or invalid state.'], 400);
        }

        if (!$exam->started_at) {
            $exam->started_at = now();
            $exam->status = 'In Progress'; // Or custom status
            $exam->save();
        }

        $setup = $exam->application->program->examinationSetup;
        if (!$setup) {
            return response()->json(['message' => 'Examination setup not found for this program.'], 400);
        }

        $questionnaire = $setup->questionnaire()->with('questions.choices')->first();
        $questions = $questionnaire->questions;

        if ($setup->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        // Hide correct answers from the response
        $questions->each(function ($question) {
            $question->max_answers = $question->choices->where('is_correct', true)->count();
            $question->choices->each(function ($choice) {
                $choice->makeHidden('is_correct');
            });
        });

        return response()->json([
            'examination' => $exam,
            'setup' => $setup,
            'questionnaire' => [
                'title' => $questionnaire->title,
                'description' => $questionnaire->description,
                'questions' => $questions
            ]
        ]);
    }

    public function submit(Request $request, $examination_id)
    {
        $exam = EcesproExamination::with('application.program.examinationSetup')->findOrFail($examination_id);
        
        if ($exam->application->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($exam->completed_at) {
            return response()->json(['message' => 'Exam already submitted.'], 400);
        }

        $setup = $exam->application->program->examinationSetup;
        $questions = $setup->questionnaire->questions()->with('choices')->get()->keyBy('id');

        $answers = $request->input('answers', []); // array of {question_id: 1, selected_choices: [1,2], answer_text: "..."}
        
                $totalPoints = $questions->sum('points') ?? $questions->count();
        if ($totalPoints == 0) { $totalPoints = $questions->count(); }
        $earnedPoints = 0;

        $insertData = [];
        $now = now();

        foreach ($answers as $ans) {
            $question = $questions->get($ans['question_id']);
            if (!$question) continue;

            $isCorrect = false;

            if (in_array($question->type, ['multiple_choice', 'true_false'])) {
                $selectedChoiceIds = $ans['selected_choices'] ?? [];
                if (!is_array($selectedChoiceIds)) {
                    $selectedChoiceIds = [$selectedChoiceIds];
                }

                $correctChoiceIds = $question->choices->where('is_correct', true)->pluck('id')->toArray();
                
                sort($selectedChoiceIds);
                sort($correctChoiceIds);
                $isCorrect = ($selectedChoiceIds === $correctChoiceIds);

                foreach ($selectedChoiceIds as $choiceId) {
                    $insertData[] = [
                        'ecespro_examination_id' => $exam->id,
                        'question_id' => $question->id,
                        'answer_choice_id' => $choiceId,
                        'is_correct' => in_array($choiceId, $correctChoiceIds),
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            } elseif ($question->type === 'fill_in_blank') {
                $applicantText = trim($ans['answer_text'] ?? '');
                $correctText = trim($question->correct_answer_text ?? '');
                $isCorrect = (strtolower($applicantText) === strtolower($correctText));

                $insertData[] = [
                    'ecespro_examination_id' => $exam->id,
                    'question_id' => $question->id,
                    'answer_text' => $applicantText,
                    'is_correct' => $isCorrect,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            } elseif ($question->type === 'modified_true_false') {
                $selectedChoiceIds = $ans['selected_choices'] ?? [];
                if (!is_array($selectedChoiceIds)) {
                    $selectedChoiceIds = [$selectedChoiceIds];
                }
                
                $correctChoiceIds = $question->choices->where('is_correct', true)->pluck('id')->toArray();
                
                sort($selectedChoiceIds);
                sort($correctChoiceIds);
                
                $choiceIsCorrect = ($selectedChoiceIds === $correctChoiceIds);
                
                if ($choiceIsCorrect) {
                    $correctChoiceTexts = $question->choices->whereIn('id', $correctChoiceIds)->pluck('choice_text')->map(fn($t) => strtolower(trim($t)))->toArray();
                    
                    if (in_array('false', $correctChoiceTexts)) {
                        $applicantText = trim($ans['answer_text'] ?? '');
                        $correctText = trim($question->correct_answer_text ?? '');
                        $isCorrect = (strtolower($applicantText) === strtolower($correctText));
                    } else {
                        $isCorrect = true;
                    }
                } else {
                    $isCorrect = false;
                }

                $applicantText = trim($ans['answer_text'] ?? '');
                foreach ($selectedChoiceIds as $choiceId) {
                    $insertData[] = [
                        'ecespro_examination_id' => $exam->id,
                        'question_id' => $question->id,
                        'answer_choice_id' => $choiceId,
                        'answer_text' => $applicantText,
                        'is_correct' => $isCorrect,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }

            if ($isCorrect) {
                $earnedPoints += $question->points ?? 1;
            }
        }

        if (!empty($insertData)) {
            EcesproApplicantExamAnswer::insert($insertData);
        }

        $percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $passed = $percentage >= $setup->passing_percentage;

        $exam->score = $earnedPoints . '/' . $totalPoints;
        $exam->status = $passed ? 'Passed' : 'Failed';
        $exam->completed_at = now();
        $exam->save();

        return response()->json([
            'message' => 'Exam submitted successfully.',
            'score' => $exam->score,
            'percentage' => $percentage,
            'status' => $exam->status
        ]);
    }
}




