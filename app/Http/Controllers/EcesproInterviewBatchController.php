<?php

namespace App\Http\Controllers;

use App\Models\EcesproApplication;
use App\Models\EcesproInterview;
use App\Models\EcesproInterviewBatch;
use App\Notifications\EcesproApplicationStatusNotification;
use App\Notifications\BatchCancelledNotification;
use Illuminate\Http\Request;

class EcesproInterviewBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproInterviewBatch::with(['interviews.application.program', 'interviews.application.user.youthProfile'])->latest('created_at')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:255',
            'interview_date' => 'required|date',
            'time' => 'required|string',
            'panel' => 'required|string',
            'mode' => 'required|string',
            'status' => 'nullable|string',
            'applicants' => 'nullable|array',
            'applicants.*.applicantId' => 'required|exists:ecespro_applications,id',
        ]);

        // Use a transaction so everything rolls back if something fails
        $batch = \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $batch = EcesproInterviewBatch::create([
                'batch_name' => $validated['batch_name'],
                'interview_date' => $validated['interview_date'],
                'time' => $validated['time'] ?? null,
                'panel' => $validated['panel'] ?? null,
                'mode' => $validated['mode'] ?? null,
                'status' => $validated['status'] ?? 'Scheduled',
            ]);

            if (isset($validated['applicants']) && !empty($validated['applicants'])) {
                $now = now();
                $insertData = [];
                $applicantIds = [];

                foreach ($validated['applicants'] as $applicant) {
                    $insertData[] = [
                        'ecespro_interview_batch_id' => $batch->id,
                        'ecespro_application_id' => $applicant['applicantId'],
                        'status' => 'Pending',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $applicantIds[] = $applicant['applicantId'];
                }

                EcesproInterview::insert($insertData);

                EcesproApplication::whereIn('id', $applicantIds)
                    ->update(['application_status' => 'Interview Scheduled']);
            }

            return $batch;
        });

        // Send notifications AFTER the transaction commits (so a notification failure doesn't roll back the batch)
        if (isset($validated['applicants']) && !empty($validated['applicants'])) {
            $applicantIds = array_column($validated['applicants'], 'applicantId');
            $applications = EcesproApplication::with('user')->whereIn('id', $applicantIds)->get();

            foreach ($applications as $app) {
                try {
                    if ($app && $user = $app->user) {
                        $msg = "Your ECESPRO Panel Interview has been scheduled! Date: {$batch->interview_date}, Time: {$batch->time}, Panel: {$batch->panel}, Mode: {$batch->mode} (Batch: {$batch->batch_name}).";
                        $metadata = [
                            'batch_name' => $batch->batch_name,
                            'interview_date' => $batch->interview_date,
                            'time' => $batch->time,
                            'panel' => $batch->panel,
                            'mode' => $batch->mode,
                        ];
                        $user->notify(new EcesproApplicationStatusNotification($app, 'Interview Scheduled', $msg, $metadata));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Interview notification failed for application ' . $app->id . ': ' . $e->getMessage());
                }
            }
        }

        return $batch->load(['interviews.application.program', 'interviews.application.user.youthProfile']);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproInterviewBatch $ecesproInterviewBatch)
    {
        return $ecesproInterviewBatch->load(['interviews.application.program', 'interviews.application.user.youthProfile']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproInterviewBatch $ecesproInterviewBatch)
    {
        $validated = $request->validate([
            'batch_name' => 'sometimes|string|max:255',
            'interview_date' => 'sometimes|date',
            'time' => 'sometimes|required|string',
            'panel' => 'sometimes|required|string',
            'mode' => 'sometimes|required|string',
            'status' => 'nullable|string',
        ]);

        $ecesproInterviewBatch->update($validated);

        return $ecesproInterviewBatch;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, EcesproInterviewBatch $ecesproInterviewBatch)
    {
        if ($ecesproInterviewBatch->status === 'Interview Completed') {
            return response()->json(['message' => 'Cannot delete a completed interview batch.'], 403);
        }

        $hasNonPending = $ecesproInterviewBatch->interviews()->where('status', '!=', 'Pending')->exists();
        if ($hasNonPending) {
            return response()->json(['message' => 'Cannot delete batch because some applicants already have an interview result.'], 403);
        }

        $reason = $request->input('remarks') ?? 'No reason provided';
        
        $appIds = $ecesproInterviewBatch->interviews()->pluck('ecespro_application_id');
        $applications = \App\Models\EcesproApplication::with('user')->whereIn('id', $appIds)->get();
        
        foreach ($applications as $app) {
            if ($app->user) {
                $app->user->notify(new BatchCancelledNotification(
                    $ecesproInterviewBatch->batch_name,
                    'Panel Interview',
                    $reason
                ));
            }
        }

        \App\Models\EcesproApplication::whereIn('id', $appIds)->update(['application_status' => 'Qualified for Interview']);

        $ecesproInterviewBatch->interviews()->delete();
        $ecesproInterviewBatch->delete();

        return response()->noContent();
    }

    public function markAsComplete($id)
    {
        $batch = \App\Models\EcesproInterviewBatch::findOrFail($id);

        // Check if any applicant has Pending status
        $pendingApplicantsCount = \App\Models\EcesproInterview::where('ecespro_interview_batch_id', $batch->id)
            ->where('status', 'Pending')->count();

        if ($pendingApplicantsCount > 0) {
            return response()->json(['message' => 'Cannot mark as complete. ' . $pendingApplicantsCount . ' applicant(s) still have a Pending status. Please grade or modify them first.'], 400);
        }

        $batch->update(['status' => 'Interview Completed']);
        return response()->json(['message' => 'Interview batch marked as completed successfully.']);
    }
}

