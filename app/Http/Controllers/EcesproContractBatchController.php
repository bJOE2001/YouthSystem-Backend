<?php

namespace App\Http\Controllers;

use App\Models\EcesproApplication;
use App\Models\EcesproContract;
use App\Models\EcesproContractBatch;
use App\Notifications\EcesproApplicationStatusNotification;
use App\Notifications\BatchCancelledNotification;
use Illuminate\Http\Request;

class EcesproContractBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproContractBatch::with('contracts.application.user.youthProfile')->latest('created_at')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:255',
            'signing_date' => 'required|date',
            'time' => 'required|string',
            'venue' => 'required|string',
            'status' => 'nullable|string',
            'applicants' => 'nullable|array',
            'applicants.*.applicantId' => 'required|exists:ecespro_applications,id',
        ]);

        $batch = EcesproContractBatch::create([
            'batch_name' => $validated['batch_name'],
            'signing_date' => $validated['signing_date'],
            'time' => $validated['time'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'status' => $validated['status'] ?? null,
        ]);

        if (isset($validated['applicants']) && !empty($validated['applicants'])) {
            $now = now();
            $insertData = [];
            $applicantIds = [];

            foreach ($validated['applicants'] as $applicant) {
                $insertData[] = [
                    'ecespro_contract_batch_id' => $batch->id,
                    'ecespro_application_id' => $applicant['applicantId'],
                    'status' => 'Pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $applicantIds[] = $applicant['applicantId'];
            }

            EcesproContract::insert($insertData);

            EcesproApplication::whereIn('id', $applicantIds)
                ->update(['application_status' => 'Contract Scheduled']);

            $applications = EcesproApplication::with('user')->whereIn('id', $applicantIds)->get();
            foreach ($applications as $app) {
                if ($user = $app->user) {
                    $msg = "Your ECESPRO Contract Signing & Orientation has been scheduled! Date: {$batch->signing_date}, Time: {$batch->time}, Venue: {$batch->venue} (Batch: {$batch->batch_name}).";
                    $user->notify(new EcesproApplicationStatusNotification($app, 'Contract Scheduled', $msg));
                }
            }
        }

        return $batch->load(['contracts.application.user']);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproContractBatch $ecesproContractBatch)
    {
        return $ecesproContractBatch->load('contracts.application.user.youthProfile');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproContractBatch $ecesproContractBatch)
    {
        $validated = $request->validate([
            'batch_name' => 'sometimes|string|max:255',
            'signing_date' => 'sometimes|date',
            'time' => 'sometimes|required|string',
            'venue' => 'sometimes|required|string',
            'status' => 'nullable|string',
        ]);

        $ecesproContractBatch->update($validated);

        return $ecesproContractBatch;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, EcesproContractBatch $ecesproContractBatch)
    {

        if ($ecesproContractBatch->status === 'Contract Completed') {
            return response()->json(['message' => 'Cannot delete a completed contract signing batch.'], 403);
        }

        $hasNonPending = $ecesproContractBatch->contracts()->where('status', '!=', 'Pending')->exists();
        if ($hasNonPending) {
            return response()->json(['message' => 'Cannot delete batch because some applicants already have a contract result.'], 403);
        }

        $reason = $request->input('remarks') ?? 'No reason provided';

        $appIds = $ecesproContractBatch->contracts()->pluck('ecespro_application_id');
        $applications = \App\Models\EcesproApplication::with('user')->whereIn('id', $appIds)->get();

        foreach ($applications as $app) {
            if ($app->user) {
                $app->user->notify(new BatchCancelledNotification(
                    $ecesproContractBatch->batch_name,
                    'Contract Signing',
                    $reason
                ));
            }
        }

        \App\Models\EcesproApplication::whereIn('id', $appIds)->update(['application_status' => 'Qualified for Contract']);

        $ecesproContractBatch->contracts()->delete();
        $ecesproContractBatch->delete();

        return response()->noContent();
    }

    public function markAsComplete($id)
    {
        $batch = \App\Models\EcesproContractBatch::with('contracts.application.program')->findOrFail($id);

        $batch->update(['status' => 'Contract Completed']);

        $programs = collect();
        foreach ($batch->contracts as $contract) {
            if ($contract->application && $contract->application->program) {
                $programs->put($contract->application->program->id, $contract->application->program);
            }
        }

        foreach ($programs as $program) {
            $hasIncompleteBatches = \App\Models\EcesproContractBatch::whereHas('contracts.application', function ($query) use ($program) {
                $query->where('ecespro_program_id', $program->id);
            })->where('status', '!=', 'Contract Completed')->exists();

            if (!$hasIncompleteBatches) {
                $program->update(['status' => 'Closed']);
            }
        }

        return response()->json(['message' => 'Contract signing batch marked as completed successfully.']);
    }
}

