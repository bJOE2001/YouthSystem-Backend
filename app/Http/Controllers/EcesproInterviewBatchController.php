<?php

namespace App\Http\Controllers;

use App\Models\EcesproApplication;
use App\Models\EcesproInterview;
use App\Models\EcesproInterviewBatch;
use App\Notifications\EcesproApplicationStatusNotification;
use Illuminate\Http\Request;

class EcesproInterviewBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproInterviewBatch::with('interviews.application.user')->latest('created_at')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:255',
            'interview_date' => 'required|date',
            'time' => 'nullable|string',
            'panel' => 'nullable|string',
            'mode' => 'nullable|string',
            'status' => 'nullable|string',
            'applicants' => 'nullable|array',
            'applicants.*.applicantId' => 'required|exists:ecespro_applications,id',
        ]);

        $batch = EcesproInterviewBatch::create([
            'batch_name' => $validated['batch_name'],
            'interview_date' => $validated['interview_date'],
            'time' => $validated['time'] ?? null,
            'panel' => $validated['panel'] ?? null,
            'mode' => $validated['mode'] ?? null,
            'status' => $validated['status'] ?? 'Scheduled',
        ]);

        if (isset($validated['applicants'])) {
            foreach ($validated['applicants'] as $applicant) {
                EcesproInterview::create([
                    'ecespro_interview_batch_id' => $batch->id,
                    'ecespro_application_id' => $applicant['applicantId'],
                    'status' => 'Pending',
                ]);

                $app = EcesproApplication::find($applicant['applicantId']);
                if ($app) {
                    $app->update(['application_status' => 'Interview Scheduled']);
                    if ($user = $app->user) {
                        $msg = "Your ECESPRO Panel Interview has been scheduled! Date: {$batch->interview_date}, Time: {$batch->time}, Panel: {$batch->panel}, Mode: {$batch->mode} (Batch: {$batch->batch_name}).";
                        $user->notify(new EcesproApplicationStatusNotification($app, 'Interview Scheduled', $msg));
                    }
                }
            }
        }

        return $batch->load(['interviews.application.user']);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproInterviewBatch $ecesproInterviewBatch)
    {
        return $ecesproInterviewBatch->load('interviews.application.user');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproInterviewBatch $ecesproInterviewBatch)
    {
        $validated = $request->validate([
            'batch_name' => 'sometimes|string|max:255',
            'interview_date' => 'sometimes|date',
            'time' => 'nullable|string',
            'panel' => 'nullable|string',
            'mode' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $ecesproInterviewBatch->update($validated);

        return $ecesproInterviewBatch;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproInterviewBatch $ecesproInterviewBatch)
    {
        $ecesproInterviewBatch->interviews()->delete();
        $ecesproInterviewBatch->delete();

        return response()->noContent();
    }
}
