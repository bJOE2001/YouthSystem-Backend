<?php

namespace App\Http\Controllers;

use App\Models\EcesproApplication;
use App\Models\EcesproExamBatch;
use App\Models\EcesproExamination;
use App\Notifications\EcesproApplicationStatusNotification;
use Illuminate\Http\Request;

class EcesproExamBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproExamBatch::with(['examinations.application.user.youthProfile', 'examinations.application.program'])->latest('created_at')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:255',
            'exam_date' => 'required|date',
            'time' => 'nullable|string',
            'venue' => 'nullable|string',
            'status' => 'nullable|string',
            'applicants' => 'nullable|array',
            'applicants.*.applicantId' => 'required|exists:ecespro_applications,id',
        ]);

        $batch = EcesproExamBatch::create([
            'batch_name' => $validated['batch_name'],
            'exam_date' => $validated['exam_date'],
            'time' => $validated['time'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'status' => $validated['status'] ?? 'Scheduled',
        ]);

        if (isset($validated['applicants'])) {
            foreach ($validated['applicants'] as $applicant) {
                EcesproExamination::create([
                    'ecespro_exam_batch_id' => $batch->id,
                    'ecespro_application_id' => $applicant['applicantId'],
                    'status' => 'Pending',
                ]);

                $app = EcesproApplication::find($applicant['applicantId']);
                if ($app) {
                    $app->update(['application_status' => 'Exam Scheduled']);
                    if ($user = $app->user) {
                        $msg = "Your ECESPRO Qualifying Examination has been scheduled! Date: {$batch->exam_date}, Time: {$batch->time}, Venue: {$batch->venue} (Batch: {$batch->batch_name}).";
                        $metadata = [
                            'batch_name' => $batch->batch_name,
                            'exam_date' => $batch->exam_date,
                            'time' => $batch->time,
                            'venue' => $batch->venue,
                        ];
                        $user->notify(new EcesproApplicationStatusNotification($app, 'Exam Scheduled', $msg, $metadata));
                    }
                }
            }
        }

        return $batch;
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproExamBatch $ecesproExamBatch)
    {
        return $ecesproExamBatch->load(['examinations.application.user.youthProfile', 'examinations.application.program.examinationSetup']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproExamBatch $ecesproExamBatch)
    {
        $validated = $request->validate([
            'batch_name' => 'sometimes|string|max:255',
            'exam_date' => 'sometimes|date',
            'time' => 'nullable|string',
            'venue' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $ecesproExamBatch->update($validated);

        return $ecesproExamBatch;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproExamBatch $ecesproExamBatch)
    {
        $ecesproExamBatch->examinations()->delete();
        $ecesproExamBatch->delete();

        return response()->noContent();
    }

    public function toggleExamStatus(Request $request, EcesproExamBatch $ecesproExamBatch)
    {
        $ecesproExamBatch->update([
            'is_exam_enabled' => !$ecesproExamBatch->is_exam_enabled
        ]);

        return response()->json($ecesproExamBatch);
    }
        public function getEssayQuestions($id)
    {
        $batch = EcesproExamBatch::with(['examinations.answers' => function($q) {
            $q->whereHas('question', function($q2) {
                $q2->where('type', 'essay');
            })->whereNull('awarded_points')->with('question');
        }])->findOrFail($id);

        $questions = collect();

        foreach ($batch->examinations as $exam) {
            foreach ($exam->answers as $answer) {
                $qId = $answer->question_id;
                if (!$questions->has($qId)) {
                    $questions->put($qId, [
                        'question' => $answer->question,
                        'pending_count' => 0
                    ]);
                }
                
                $qData = $questions->get($qId);
                $qData['pending_count']++;
                $questions->put($qId, $qData);
            }
        }

        return response()->json(array_values($questions->toArray()));
    }

    public function getPendingAnswersForQuestion($batchId, $questionId)
    {
        $batch = EcesproExamBatch::with(['examinations' => function($q) use ($questionId) {
            $q->with(['application.user.youthProfile', 'answers' => function($q2) use ($questionId) {
                $q2->where('question_id', $questionId)->whereNull('awarded_points')->with('question');
            }]);
        }])->findOrFail($batchId);

        $answers = [];
        foreach ($batch->examinations as $exam) {
            foreach ($exam->answers as $ans) {
                $answers[] = [
                    'examination_id' => $exam->id,
                    'answer_id' => $ans->id,
                    'applicant_name' => trim(($exam->application->user->youthProfile->first_name ?? '') . ' ' . ($exam->application->user->youthProfile->last_name ?? '')),
                    'application_no' => 'APP-' . str_pad($exam->application->id, 4, '0', STR_PAD_LEFT),
                    'profile_picture' => $exam->application->user->youthProfile->profile_picture ?? '',
                    'answer_text' => $ans->answer_text,
                    'question_points' => $ans->question->points ?? 10
                ];
            }
        }

        return response()->json($answers);
    }

        public function gradeSingleAnswer(Request $request, $batchId, $answerId)
    {
        $request->validate([
            'awarded_points' => 'required|numeric|min:0'
        ]);

        $answer = \App\Models\EcesproApplicantExamAnswer::with('examination')->findOrFail($answerId);
        
        if ($answer->awarded_points !== null) {
            return response()->json(['message' => 'Answer is already graded'], 400);
        }

        $answer->awarded_points = $request->awarded_points;
        $answer->is_correct = $request->awarded_points > 0;
        $answer->save();

        // Increment exam score
        $exam = $answer->examination;
        $exam->score = ($exam->score ?? 0) + $request->awarded_points;
        $exam->save();

        // Check if there are any pending essays left for this examination
        $pendingCount = \App\Models\EcesproApplicantExamAnswer::where('ecespro_examination_id', $exam->id)
            ->whereHas('question', function($q) {
                $q->where('type', 'essay');
            })
            ->whereNull('awarded_points')
            ->count();

        if ($pendingCount === 0) {
            $exam->load('application.program.examinationSetup');
            $setup = $exam->application->program->examinationSetup ?? null;
            if ($setup) {
                $totalPossiblePoints = \App\Models\EcesproExamQuestion::where('questionnaire_id', $setup->questionnaire_id)->sum('points');
                $passingPercentage = $setup->passing_percentage ?? 50;
                $passingScore = ($passingPercentage / 100) * $totalPossiblePoints;
                $exam->status = $exam->score >= $passingScore ? 'Passed' : 'Failed';
                $exam->save();

                if ($exam->status === 'Passed') {
                    $exam->application()->update([
                        'application_status' => 'Qualified for Interview'
                    ]);
                } else if ($exam->status === 'Failed') {
                    $exam->application()->update([
                        'application_status' => 'Failed Exam'
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Answer graded successfully', 'remaining_pending' => $pendingCount, 'new_status' => $exam->status]);
    }
}
