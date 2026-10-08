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

    public function eligibility(Request $request)
    {
        $schoolYear = $request->query('school_year');
        $semester = $request->query('semester');

        if (!$schoolYear || !$semester) {
            return response()->json([]);
        }

        $scholars = EcesproScholar::with(['user.youthProfile', 'application', 'grantReleases.batch', 'scholarPosition'])->get();

        $scholars->transform(function ($scholar) use ($schoolYear, $semester) {
            $pastGrants = $scholar->grantReleases;

            $releasedGrants = $pastGrants->filter(function ($grant) {
                return strtolower($grant->status) === 'released' ||
                    ($grant->batch && strtolower($grant->batch->status) === 'completed');
            });

            // Check if they are in ANY batch for EXACT school_year & semester (released or not)
            $hasReceivedCurrent = $pastGrants->contains(function ($grant) use ($schoolYear, $semester) {
                return $grant->batch &&
                    strtolower(trim($grant->batch->school_year)) === strtolower(trim($schoolYear)) &&
                    strtolower(trim($grant->batch->semester)) === strtolower(trim($semester));
            });

            $scholarType = $releasedGrants->isEmpty() ? 'New' : 'Continuing';
            $scholar->scholar_type = $scholarType;
            if ($hasReceivedCurrent) {
                $scholar->is_eligible = false;
                $scholar->eligibility_reason = "Scholar has already received or is currently in a batch for {$semester} SY {$schoolYear}.";
            } else {
                if ($scholarType === 'Continuing') {
                    // Check for validated compliance in requirements_history
                    $hasValidatedCompliance = false;
                    $history = is_array($scholar->requirements_history) ? $scholar->requirements_history : json_decode($scholar->requirements_history, true);

                    if (!empty($history)) {
                        foreach ($history as $req) {
                            $reqSchoolYear = $req['school_year'] ?? $req['schoolYear'] ?? '';
                            $reqSemester = $req['semester'] ?? '';
                            $reqStatus = $req['status'] ?? '';
                            if (
                                strtolower(trim($reqSchoolYear)) === strtolower(trim($schoolYear)) &&
                                strtolower(trim($reqSemester)) === strtolower(trim($semester)) &&
                                strtolower(trim($reqStatus)) === 'validated'
                            ) {
                                $hasValidatedCompliance = true;
                                break; // Stop looping once we found a valid compliance
                            }
                        }
                    }
                    if (!$hasValidatedCompliance) {
                        $scholar->is_eligible = false;
                        $scholar->eligibility_reason = "Not Eligible: Missing or unvalidated compliance for {$semester} SY {$schoolYear}.";
                    } else {
                        $scholar->is_eligible = true;
                        $scholar->eligibility_reason = "Eligible for {$semester} SY {$schoolYear} release (Validated Compliance).";
                    }
                } else {
                    // New scholars are eligible without previous compliance
                    $scholar->is_eligible = true;
                    $scholar->eligibility_reason = "Eligible for {$semester} SY {$schoolYear} release.";
                }
            }

            return $scholar;
        });

        return response()->json($scholars);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:255',
            'release_date' => 'required|date',
            'time' => 'required|string|max:255',
            'venue' => 'required|string|max:255',
            'school_year' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:255',
            'notify_scholars' => 'boolean',
            'scholars' => 'required|array|min:1',
            'scholars.*.scholarId' => 'required|exists:ecespro_scholars,id',
        ]);

        DB::beginTransaction();

        try {
            $schoolYear = $validated['school_year'] ?? null;
            $semester = $validated['semester'] ?? null;

            // Backend secure evaluation of all passed scholars
            $scholarIds = collect($validated['scholars'])->pluck('scholarId')->toArray();
            $scholars = EcesproScholar::with(['grantReleases.batch'])->whereIn('id', $scholarIds)->get();

            foreach ($scholars as $scholar) {
                $pastGrants = $scholar->grantReleases;

                // Check if they already are in a batch for exactly this school year and semester
                if ($schoolYear && $semester) {
                    $hasReceivedCurrent = $pastGrants->contains(function ($grant) use ($schoolYear, $semester) {
                        return $grant->batch &&
                            strtolower(trim($grant->batch->school_year)) === strtolower(trim($schoolYear)) &&
                            strtolower(trim($grant->batch->semester)) === strtolower(trim($semester));
                    });

                    if ($hasReceivedCurrent) {
                        throw new \Exception("Scholar {$scholar->id} is already in a batch for {$semester} SY {$schoolYear}.");
                    }
                }
            }

            $batch = EcesproGrantReleaseBatch::create([
                'batch_name' => $validated['batch_name'],
                'release_date' => $validated['release_date'],
                'time' => $validated['time'],
                'venue' => $validated['venue'],
                'school_year' => $schoolYear,
                'semester' => $semester,
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

            return response()->json(['message' => 'Failed to create batch: ' . $e->getMessage()], 500);
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

    public function markReleased($grantReleaseId)
    {
        $grantRelease = EcesproGrantRelease::findOrFail($grantReleaseId);
        $grantRelease->update(['status' => 'Released']);

        return response()->json(['message' => 'Grant marked as released successfully']);
    }
}
