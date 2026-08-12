<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\EcesproExamination;
use App\Notifications\EcesproApplicationStatusNotification;
use Illuminate\Http\Request;

class EcesproExaminationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproExamination::with(['application', 'batch'])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ecespro_application_id' => 'required|exists:ecespro_applications,id',
            'ecespro_exam_batch_id' => 'nullable|exists:ecespro_exam_batches,id',
            'score' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        return EcesproExamination::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproExamination $ecesproExamination)
    {
        return $ecesproExamination->load(['application', 'batch']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproExamination $ecesproExamination)
    {
        $validated = $request->validate([
            'ecespro_application_id' => 'sometimes|exists:ecespro_applications,id',
            'ecespro_exam_batch_id' => 'nullable|exists:ecespro_exam_batches,id',
            'score' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $ecesproExamination->update($validated);

        if (isset($validated['status'])) {
            $newStatus = $validated['status'] === 'Passed' ? 'Qualified for Interview' : ($validated['status'] === 'Failed' ? 'Failed Exam' : null);
            if ($newStatus) {
                $ecesproExamination->application()->update([
                    'application_status' => $newStatus,
                ]);
                $app = $ecesproExamination->application;
                if ($app && $user = $app->user) {
                    $user->notify(new EcesproApplicationStatusNotification($app, $newStatus));
                }
            }
        }

        return $ecesproExamination;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproExamination $ecesproExamination)
    {
        $ecesproExamination->delete();

        return response()->noContent();
    }



    /**
 * Bulk update scores/status for a batch of examinations.
 */
public function bulkUpdate(Request $request)
{
$validated = $request->validate([
    'updates' => ['required', 'array', 'min:1', 'max:2000'],
    'updates.*.id' => ['required', 'integer', 'exists:ecespro_examinations,id'],
    'updates.*.score' => ['nullable'], // accept anything, cast when saving
    'updates.*.status' => ['nullable', 'string', 'in:Pending,Passed,Failed'],
]);

    $updates = collect($validated['updates']);

    $succeededIds = [];
    $failedIds = [];

    DB::transaction(function () use ($updates, &$succeededIds, &$failedIds) {
        foreach ($updates->chunk(200) as $chunk) {
            foreach ($chunk as $u) {
                try {
                    $examination = EcesproExamination::find($u['id']);
                    if (!$examination) {
                        $failedIds[] = $u['id'];
                        continue;
                    }

                    $examination->update([
                        'score' => isset($u['score']) ? (string) $u['score'] : $examination->score,
                        'status' => $u['status'] ?? $examination->status,
                    ]);

                    if (isset($u['status'])) {
                        $newStatus = $u['status'] === 'Passed'
                            ? 'Qualified for Interview'
                            : ($u['status'] === 'Failed' ? 'Failed Exam' : null);

                        if ($newStatus) {
                            $examination->application()->update([
                                'application_status' => $newStatus,
                            ]);

                            $app = $examination->application;
                            if ($app && $user = $app->user) {
                                $user->notify(new EcesproApplicationStatusNotification($app, $newStatus));
                            }
                        }
                    }

                    $succeededIds[] = $u['id'];
                } catch (\Throwable $e) {
                    Log::error('Bulk examination update failed', [
                        'id' => $u['id'],
                        'error' => $e->getMessage(),
                    ]);
                    $failedIds[] = $u['id'];
                }
            }
        }
    });

    return response()->json([
        'message' => 'Bulk update completed.',
        'succeeded' => count($succeededIds),
        'failed' => count($failedIds),
        'failed_ids' => $failedIds,
    ]);
}
}

