<?php

namespace App\Http\Controllers;

use App\Models\EcesproApplication;
use App\Models\EcesproExamBatch;
use App\Models\EcesproExamination;
use App\Notifications\EcesproApplicationStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $batch = DB::transaction(function () use ($validated) {

            // 1. Create the exam batch
            $batch = EcesproExamBatch::create([
                'batch_name' => $validated['batch_name'],
                'exam_date' => $validated['exam_date'],
                'time' => $validated['time'] ?? null,
                'venue' => $validated['venue'] ?? null,
                'status' => $validated['status'] ?? 'Scheduled',
            ]);

            $applicantIds = collect($validated['applicants'] ?? [])
                ->pluck('applicantId')
                ->unique()
                ->values();

            if ($applicantIds->isEmpty()) {
                return $batch;
            }

            /*
         * 2. Load all applications + users at once.
         * Instead of SELECT for every applicant.
         */
            $applications = EcesproApplication::with('user')
                ->whereIn('id', $applicantIds)
                ->get()
                ->keyBy('id');

            /*
         * 3. Insert all examinations at once.
         */
            $now = now();

            $examinations = $applicantIds->map(function ($applicantId) use ($batch, $now) {
                return [
                    'ecespro_exam_batch_id' => $batch->id,
                    'ecespro_application_id' => $applicantId,
                    'status' => 'Pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->toArray();

            EcesproExamination::insert($examinations);

            /*
         * 4. Update all applications at once.
         */
            EcesproApplication::whereIn('id', $applicantIds)
                ->update([
                    'application_status' => 'Exam Scheduled',
                    'updated_at' => $now,
                ]);

            /*
         * 5. Send notifications.
         *
         * This is still synchronous, as you requested.
         */
            foreach ($applications as $app) {

                if (!$app->user) {
                    continue;
                }

                $msg = "Your ECESPRO Qualifying Examination has been scheduled! "
                    . "Date: {$batch->exam_date}, "
                    . "Time: {$batch->time}, "
                    . "Venue: {$batch->venue} "
                    . "(Batch: {$batch->batch_name}).";

                $metadata = [
                    'batch_name' => $batch->batch_name,
                    'exam_date' => $batch->exam_date,
                    'time' => $batch->time,
                    'venue' => $batch->venue,
                ];

                $app->user->notify(
                    new EcesproApplicationStatusNotification(
                        $app,
                        'Exam Scheduled',
                        $msg,
                        $metadata
                    )
                );
            }

            return $batch;
        });

        return response()->json($batch);
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
        if ($ecesproExamBatch->status === 'Exam Completed') {
            return response()->json(['message' => 'Cannot delete a completed exam batch.'], 403);
        }
        
        if ($ecesproExamBatch->is_exam_enabled) {
            return response()->json(['message' => 'Cannot delete an active exam batch. Disable the exam first.'], 403);
        }

        $hasNonPending = $ecesproExamBatch->examinations()->where('status', '!=', 'Pending')->exists();
        if ($hasNonPending) {
            return response()->json(['message' => 'Cannot delete batch because some applicants have already started or finished their exam.'], 403);
        }

        $appIds = $ecesproExamBatch->examinations()->pluck('ecespro_application_id');
        \App\Models\EcesproApplication::whereIn('id', $appIds)->update(['application_status' => 'Qualified for Exam']);

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
        $batch = EcesproExamBatch::with(['examinations.answers' => function ($q) {
            $q->whereHas('question', function ($q2) {
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
        $batch = EcesproExamBatch::with(['examinations' => function ($q) use ($questionId) {
            $q->with(['application.user.youthProfile', 'answers' => function ($q2) use ($questionId) {
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
            ->whereHas('question', function ($q) {
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

                // Capture the raw earned points before converting to slash format
                $earnedPoints = (float) $exam->score;
                $scorePercentage = $totalPossiblePoints > 0 ? ($earnedPoints / $totalPossiblePoints) * 100 : 0;

                // Store score in slash format for consistency
                $exam->score = $earnedPoints . '/' . $totalPossiblePoints;
                $exam->status = $scorePercentage >= $passingPercentage ? 'Passed' : 'Failed';
                $exam->completed_at = now();
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

    public function markAsComplete($id)
    {
        $batch = \App\Models\EcesproExamBatch::findOrFail($id);

        // Check if there are any pending ungraded essays or unfinished exams in this batch
        $incompleteExamsCount = \App\Models\EcesproExamination::where('ecespro_exam_batch_id', $batch->id)
            ->whereNull('completed_at')->count();

        if ($incompleteExamsCount > 0) {
            return response()->json(['message' => 'Cannot mark as complete. ' . $incompleteExamsCount . ' applicant(s) have not finished their exam.'], 400);
        }

        $ungradedEssaysCount = \App\Models\EcesproApplicantExamAnswer::whereHas('question', function ($query) {
            $query->where('type', 'essay');
        })->whereHas('examination', function ($query) use ($batch) {
            $query->where('ecespro_exam_batch_id', $batch->id);
        })->whereNull('awarded_points')->count();

        if ($ungradedEssaysCount > 0) {
            return response()->json(['message' => 'Cannot mark as complete. ' . $ungradedEssaysCount . ' essay answer(s) are pending for grading.'], 400);
        }

        $batch->update(['application_status' => 'Exam Completed']);
        return response()->json(['message' => 'Exam batch marked as completed successfully.']);
    }
}
