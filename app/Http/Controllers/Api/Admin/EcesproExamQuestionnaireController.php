<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EcesproExamQuestionnaire;
use App\Models\EcesproExamQuestion;
use App\Models\EcesproExamAnswerChoice;
use Illuminate\Http\Request;

class EcesproExamQuestionnaireController extends Controller
{
    public function index(Request $request)
    {
        $query = EcesproExamQuestionnaire::query();
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        $paginated = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 10));
        
        $paginated->getCollection()->transform(function ($questionnaire) {
            $questionnaire->is_locked = \App\Models\EcesproExamBatch::where('is_exam_enabled', true)
                ->whereHas('examinations.application.program', function ($query) use ($questionnaire) {
                    $query->where('status', '!=', 'Exam Completed')
                          ->whereHas('examinationSetup', function($q2) use ($questionnaire) {
                              $q2->where('questionnaire_id', $questionnaire->id);
                          });
                })->exists();
            return $questionnaire;
        });

        return response()->json($paginated);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $questionnaire = EcesproExamQuestionnaire::create($request->only('title', 'description'));
        return response()->json($questionnaire, 201);
    }

    public function show($id)
    {
        $questionnaire = EcesproExamQuestionnaire::with('questions.choices')->findOrFail($id);
        return response()->json($questionnaire);
    }

    public function update(Request $request, $id)
    {
        $questionnaire = EcesproExamQuestionnaire::findOrFail($id);
        $questionnaire->update($request->only('title', 'description'));
        return response()->json($questionnaire);
    }

    public function destroy($id)
    {
        EcesproExamQuestionnaire::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function storeQuestion(Request $request, $id)
    {
        $questionnaire = EcesproExamQuestionnaire::findOrFail($id);
        
        $request->validate([
            'type' => 'required|in:multiple_choice,fill_in_blank,essay,true_false',
            'question_text' => 'required|string',
            'allow_multiple_answers' => 'boolean',
            'choices' => 'array',
            'points' => 'integer|min:1',
        ]);

        $image_path = null;
        if ($request->hasFile('image')) {
            $image_path = $request->file('image')->store('exam_questions', 'public');
        }

        $question = $questionnaire->questions()->create([
            'type' => $request->type,
            'question_text' => $request->question_text,
            'allow_multiple_answers' => $request->allow_multiple_answers ?? false,
            'image_path' => $image_path,
        ]);

        if ($request->has('choices')) {
            foreach ($request->choices as $choiceData) {
                // If it's a JSON array of objects passed via form-data, we might need to json_decode it.
                // Assuming normal array for now
                $choice = is_string($choiceData) ? json_decode($choiceData, true) : $choiceData;
                if ($choice) {
                    $question->choices()->create([
                        'choice_text' => $choice['choice_text'],
                        'is_correct' => $choice['is_correct'] ?? false,
                    ]);
                }
            }
        }

        return response()->json($question->load('choices'), 201);
    }

    public function destroyQuestion($questionnaire_id, $question_id)
    {
        EcesproExamQuestion::where('questionnaire_id', $questionnaire_id)->where('id', $question_id)->delete();
        return response()->json(['message' => 'Question deleted']);
    }

    public function updateQuestion(Request $request, $questionnaire_id, $question_id)
    {
        $questionnaire = EcesproExamQuestionnaire::findOrFail($questionnaire_id);
        $question = $questionnaire->questions()->findOrFail($question_id);
        
        $request->validate([
            'type' => 'required|in:multiple_choice,fill_in_blank,essay,true_false',
            'question_text' => 'required|string',
            'points' => 'integer|min:1',
            'allow_multiple_answers' => 'boolean',
            'choices' => 'array',
        ]);

        $image_path = $question->image_path;
        if ($request->hasFile('image')) {
            $image_path = $request->file('image')->store('exam_questions', 'public');
        } elseif ($request->has('remove_image') && $request->remove_image == 'true') {
            $image_path = null;
        }

        $question->update([
            'type' => $request->type,
            'question_text' => $request->question_text,
            'points' => $request->points ?? 1,
            'allow_multiple_answers' => $request->allow_multiple_answers ?? false,
            'image_path' => $image_path,
        ]);

        if ($request->has('choices')) {
            $question->choices()->delete();
            foreach ($request->choices as $choiceData) {
                $choice = is_string($choiceData) ? json_decode($choiceData, true) : $choiceData;
                if ($choice) {
                    $question->choices()->create([
                        'choice_text' => $choice['choice_text'],
                        'is_correct' => $choice['is_correct'] ?? false,
                    ]);
                }
            }
        }

        return response()->json($question->load('choices'), 200);
    }
}

