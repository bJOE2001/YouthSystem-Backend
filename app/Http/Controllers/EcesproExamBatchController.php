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
        return EcesproExamBatch::with('examinations.application.user.youthProfile')->latest('created_at')->get();
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
        return $ecesproExamBatch->load('examinations.application.user.youthProfile');
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
}
