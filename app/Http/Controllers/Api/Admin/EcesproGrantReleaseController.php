<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EcesproGrantRelease;
use App\Models\EcesproGrantReleaseBatch;
use App\Models\EcesproScholar;
use App\Notifications\GrantReleaseNotification;
use App\Notifications\BatchCancelledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcesproGrantReleaseController extends Controller
{
    public function index()
    {
        $batches = EcesproGrantReleaseBatch::with(['grants.scholar.user.youthProfile', 'grants.scholar.application'])
            ->orderByDesc('release_date')
            ->paginate(15);

        return response()->json($batches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:255',
            'release_date' => 'required|date',
            'time' => 'required|string|max:255',
            'venue' => 'required|string|max:255',
            'notify_scholars' => 'boolean',
            'scholars' => 'required|array|min:1',
            'scholars.*.scholarId' => 'required|exists:ecespro_scholars,id',
        ]);

        DB::beginTransaction();

        try {
            $batch = EcesproGrantReleaseBatch::create([
                'batch_name' => $validated['batch_name'],
                'release_date' => $validated['release_date'],
                'time' => $validated['time'],
                'venue' => $validated['venue'],
                'notify_scholars' => $validated['notify_scholars'] ?? false,
            ]);

            foreach ($validated['scholars'] as $scholarData) {
                EcesproGrantRelease::create([
                    'batch_id' => $batch->id,
                    'scholar_id' => $scholarData['scholarId'],
                    'status' => 'Pending',
                ]);

                if ($batch->notify_scholars) {
                    $scholar = EcesproScholar::with('user')->find($scholarData['scholarId']);
                    if ($scholar && $scholar->user) {
                        $scholar->user->notify(new GrantReleaseNotification($batch));
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Grant release batch created successfully.',
                'batch' => $batch->load('grants.scholar.user.youthProfile'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to create batch: '.$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $batch = EcesproGrantReleaseBatch::with(['grants.scholar.user.youthProfile', 'grants.scholar.application'])->findOrFail($id);

        return response()->json($batch);
    }

    public function destroy(Request $request, $id)
    {
        $batch = EcesproGrantReleaseBatch::findOrFail($id);

        $reason = $request->input('remarks') ?? 'No reason provided';
        
        $scholarIds = EcesproGrantRelease::where('batch_id', $batch->id)->pluck('scholar_id');
        $scholars = EcesproScholar::with('user')->whereIn('id', $scholarIds)->get();
        
        foreach ($scholars as $scholar) {
            if ($scholar->user) {
                $scholar->user->notify(new BatchCancelledNotification(
                    $batch->batch_name,
                    'Grant Release',
                    $reason
                ));
            }
        }

        $batch->delete(); // Cascades grants due to DB foreign key cascade

        return response()->json(['message' => 'Batch deleted successfully']);
    }

    public function removeFromBatch($grantReleaseId)
    {
        $grantRelease = EcesproGrantRelease::findOrFail($grantReleaseId);
        $grantRelease->delete();

        return response()->json(['message' => 'Scholar removed from batch successfully']);
    }
}
