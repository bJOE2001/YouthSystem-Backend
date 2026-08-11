<?php

namespace App\Http\Controllers;

use App\Models\EcesproComplianceSchedule;
use App\Models\EcesproScholar;
use App\Notifications\NewComplianceScheduleNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EcesproComplianceScheduleController extends Controller
{
    /**
     * Display a listing of compliance schedules.
     */
    public function index(Request $request)
    {
        $schedules = EcesproComplianceSchedule::latest()->get();
        $scholars = EcesproScholar::all();

        // Append submitted count
        $data = $schedules->map(function ($schedule) use ($scholars) {
            $submittedCount = 0;
            foreach ($scholars as $scholar) {
                $hasSubmission = false;
                foreach ($scholar->requirements_history ?? [] as $item) {
                    $itemSy = $item['schoolYear'] ?? $item['school_year'] ?? '';
                    $itemSem = $item['semester'] ?? '';
                    if (
                        strtolower(trim($itemSy)) === strtolower(trim($schedule->school_year)) &&
                        strtolower(trim($itemSem)) === strtolower(trim($schedule->semester))
                    ) {
                        $hasSubmission = true;
                        break;
                    }
                }
                if ($hasSubmission) {
                    $submittedCount++;
                }
            }

            return [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'schoolYear' => $schedule->school_year,
                'semester' => $schedule->semester,
                'startDate' => $schedule->start_date ? $schedule->start_date->format('Y-m-d') : null,
                'endDate' => $schedule->end_date ? $schedule->end_date->format('Y-m-d') : null,
                'status' => $schedule->status,
                'instructions' => $schedule->instructions,
                'requiredDocuments' => $schedule->required_documents ?? [],
                'submittedCount' => $submittedCount,
                'createdAt' => $schedule->created_at,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Store a newly created compliance schedule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'school_year' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|in:Open,Closed,Completed',
            'instructions' => 'nullable|string',
            'required_documents' => 'nullable|array',
            'requiredDocuments' => 'nullable|array',
        ]);

        if (empty($validated['status'])) {
            $validated['status'] = 'Open';
        }

        if (isset($validated['requiredDocuments']) && ! isset($validated['required_documents'])) {
            $validated['required_documents'] = $validated['requiredDocuments'];
        }

        $schedule = EcesproComplianceSchedule::create($validated);

        // Notify active scholars
        $scholars = EcesproScholar::with('user')->where('status', 'Active')->get();
        $users = $scholars->pluck('user')->filter();
        if ($users->isNotEmpty()) {
            Notification::send($users, new NewComplianceScheduleNotification($schedule));
        }

        return response()->json([
            'message' => 'Compliance schedule created successfully.',
            'data' => $schedule,
        ]);
    }

    /**
     * Display the specified compliance schedule.
     */
    public function show($id)
    {
        $schedule = EcesproComplianceSchedule::findOrFail($id);

        return response()->json(['data' => $schedule]);
    }

    /**
     * Update the specified compliance schedule.
     */
    public function update(Request $request, $id)
    {
        $schedule = EcesproComplianceSchedule::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'school_year' => 'sometimes|required|string|max:255',
            'semester' => 'sometimes|required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|in:Open,Closed,Completed',
            'instructions' => 'nullable|string',
            'required_documents' => 'nullable|array',
            'requiredDocuments' => 'nullable|array',
        ]);

        if (isset($validated['requiredDocuments']) && ! isset($validated['required_documents'])) {
            $validated['required_documents'] = $validated['requiredDocuments'];
        }

        $schedule->update($validated);

        return response()->json([
            'message' => 'Compliance schedule updated successfully.',
            'data' => $schedule,
        ]);
    }

    /**
     * Remove the specified compliance schedule.
     */
    public function destroy($id)
    {
        $schedule = EcesproComplianceSchedule::findOrFail($id);
        $schedule->delete();

        return response()->noContent();
    }

    /**
     * Update status of compliance schedule (e.g. Open/Closed).
     */
    public function updateStatus(Request $request, $id)
    {
        $schedule = EcesproComplianceSchedule::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:Open,Closed,Completed',
        ]);

        $schedule->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Compliance schedule status updated to '.$schedule->status.'.',
            'data' => $schedule,
        ]);
    }

    /**
     * Get all scholar submissions for a specific compliance schedule.
     */
    public function submissions(Request $request, $id)
    {
        $schedule = EcesproComplianceSchedule::findOrFail($id);
        $scholars = EcesproScholar::with(['user', 'application'])->get();
        $submissions = [];

        foreach ($scholars as $scholar) {
            $history = $scholar->requirements_history ?? [];
            if (! is_array($history)) {
                continue;
            }

            $scholarItems = [];
            $historyIndexes = [];
            $allFiles = [];
            $gpa = null;
            $submittedAt = null;

            foreach ($history as $index => $item) {
                $itemSy = $item['schoolYear'] ?? $item['school_year'] ?? '';
                $itemSem = $item['semester'] ?? '';

                if (
                    strtolower(trim($itemSy)) === strtolower(trim($schedule->school_year)) &&
                    strtolower(trim($itemSem)) === strtolower(trim($schedule->semester))
                ) {
                    $historyIndexes[] = $index;
                    $scholarItems[] = $item;

                    if (empty($gpa)) {
                        $gpa = $item['general_average'] ?? $item['generalAverage'] ?? null;
                    }
                    if (empty($submittedAt)) {
                        $submittedAt = $item['submitted_at'] ?? $item['dateSubmitted'] ?? null;
                    }

                    if (! empty($item['files']) && is_array($item['files'])) {
                        foreach ($item['files'] as $f) {
                            $allFiles[] = [
                                'historyIndex' => $index,
                                'id' => $item['id'] ?? null,
                                'documentType' => $item['documentType'] ?? $f['name'] ?? 'Requirement Document',
                                'fileName' => $f['name'] ?? 'document.pdf',
                                'filePath' => $f['url'] ?? '',
                                'url' => $f['url'] ?? '',
                                'status' => $item['status'] ?? 'Pending',
                                'remarks' => $item['remarks'] ?? '',
                                'submittedAt' => $item['submitted_at'] ?? $item['dateSubmitted'] ?? null,
                            ];
                        }
                    } elseif (! empty($item['filePath'])) {
                        $allFiles[] = [
                            'historyIndex' => $index,
                            'id' => $item['id'] ?? null,
                            'documentType' => $item['documentType'] ?? 'Requirement Document',
                            'fileName' => $item['fileName'] ?? 'document.pdf',
                            'filePath' => $item['filePath'],
                            'url' => $item['filePath'],
                            'status' => $item['status'] ?? 'Pending',
                            'remarks' => $item['remarks'] ?? '',
                            'submittedAt' => $item['submitted_at'] ?? $item['dateSubmitted'] ?? null,
                        ];
                    }
                }
            }

            if (! empty($scholarItems)) {
                $statuses = array_map(fn ($i) => $i['status'] ?? 'Pending', $scholarItems);
                if (in_array('Disapproved', $statuses)) {
                    $overallStatus = 'Disapproved';
                } elseif (in_array('For Revision', $statuses) || in_array('For Resubmission', $statuses)) {
                    $overallStatus = 'For Revision';
                } elseif (in_array('Validated', $statuses) || in_array('Approved', $statuses)) {
                    $overallStatus = 'Validated';
                } else {
                    $overallStatus = 'Pending';
                }

                $remarksList = array_filter(array_map(fn ($i) => $i['remarks'] ?? '', $scholarItems));
                $overallRemarks = ! empty($remarksList) ? implode('; ', array_unique($remarksList)) : '';
            } else {
                $overallStatus = 'Not Yet Submitted';
                $overallRemarks = '';
            }

            $submissions[] = [
                'scholarId' => $scholar->id,
                'scholarNo' => $scholar->scholar_no,
                'scholarName' => $scholar->user?->name ?? $scholar->application?->full_name ?? ('Scholar #'.$scholar->id),
                'email' => $scholar->user?->email ?? $scholar->application?->email ?? '',
                'school' => $scholar->school ?? $scholar->application?->school_name ?? '',
                'course' => $scholar->course ?? $scholar->application?->course ?? '',
                'historyIndex' => $historyIndexes[0] ?? null,
                'historyIndexes' => $historyIndexes,
                'submittedAt' => $submittedAt,
                'schoolYear' => $schedule->school_year,
                'semester' => $schedule->semester,
                'generalAverage' => $gpa,
                'status' => $overallStatus,
                'remarks' => $overallRemarks,
                'files' => $allFiles,
            ];
        }

        // Sort latest submitted first
        usort($submissions, function ($a, $b) {
            return strcmp($b['submittedAt'] ?? '', $a['submittedAt'] ?? '');
        });

        return response()->json([
            'schedule' => [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'schoolYear' => $schedule->school_year,
                'semester' => $schedule->semester,
                'startDate' => $schedule->start_date ? $schedule->start_date->format('Y-m-d') : null,
                'endDate' => $schedule->end_date ? $schedule->end_date->format('Y-m-d') : null,
                'status' => $schedule->status,
                'instructions' => $schedule->instructions,
                'requiredDocuments' => $schedule->required_documents ?? [],
            ],
            'data' => $submissions,
        ]);
    }
}
