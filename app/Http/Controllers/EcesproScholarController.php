<?php

namespace App\Http\Controllers;

use App\Models\EcesproScholar;
use App\Models\EcesproVolunteerLog;
use App\Notifications\EcesproApplicationStatusNotification;
use Illuminate\Http\Request;

class EcesproScholarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproScholar::with(['user.youthProfile', 'application'])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'ecespro_application_id' => 'required|exists:ecespro_applications,id',
            'scholar_no' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'course' => 'nullable|string|max:255',
            'compliance_status' => 'nullable|string|max:255',
            'requirements_history' => 'nullable|array',
            'status' => 'nullable|string',
            'allowance_received_amount' => 'nullable|numeric|min:0',
        ]);

        return EcesproScholar::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproScholar $ecesproScholar)
    {
        return $ecesproScholar->load(['user.youthProfile', 'application']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproScholar $ecesproScholar)
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'ecespro_application_id' => 'sometimes|exists:ecespro_applications,id',
            'scholar_no' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'course' => 'nullable|string|max:255',
            'compliance_status' => 'nullable|string|max:255',
            'requirements_history' => 'nullable|array',
            'status' => 'nullable|string',
            'allowance_received_amount' => 'nullable|numeric|min:0',
        ]);

        $ecesproScholar->update($validated);

        return $ecesproScholar;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproScholar $ecesproScholar)
    {
        $ecesproScholar->delete();

        return response()->noContent();
    }

    /**
     * Get all semester compliance requirement submissions across scholars.
     */
    public function complianceValidations(Request $request)
    {
        $scholars = EcesproScholar::with(['user', 'application'])->get();
        $submissions = [];

        foreach ($scholars as $scholar) {
            $history = $scholar->requirements_history ?? [];
            if (! is_array($history)) {
                continue;
            }

            foreach ($history as $index => $item) {
                $files = [];
                if (! empty($item['files']) && is_array($item['files'])) {
                    $files = $item['files'];
                } elseif (! empty($item['filePath'])) {
                    $files = [[
                        'name' => $item['fileName'] ?? $item['documentType'] ?? 'Requirement Document',
                        'url' => $item['filePath'],
                    ]];
                }

                $submissions[] = [
                    'scholarId' => $scholar->id,
                    'scholarNo' => $scholar->scholar_no,
                    'scholarName' => $scholar->user?->name ?? $scholar->application?->full_name ?? ('Scholar #'.$scholar->id),
                    'email' => $scholar->user?->email ?? $scholar->application?->email ?? '',
                    'school' => $scholar->school ?? $scholar->application?->school_name ?? '',
                    'course' => $scholar->course ?? $scholar->application?->course ?? '',
                    'historyIndex' => $index,
                    'submittedAt' => $item['submitted_at'] ?? $item['dateSubmitted'] ?? null,
                    'schoolYear' => $item['school_year'] ?? $item['schoolYear'] ?? '',
                    'semester' => $item['semester'] ?? '',
                    'generalAverage' => $item['general_average'] ?? $item['generalAverage'] ?? null,
                    'status' => $item['status'] ?? 'Pending',
                    'remarks' => $item['remarks'] ?? '',
                    'files' => $files,
                ];
            }
        }

        // Sort latest submitted first
        usort($submissions, function ($a, $b) {
            return strcmp($b['submittedAt'] ?? '', $a['submittedAt'] ?? '');
        });

        return response()->json(['data' => $submissions]);
    }

    /**
     * Review/Validate a specific scholar requirement submission.
     */
    public function reviewCompliance(Request $request, EcesproScholar $ecesproScholar)
    {
        $request->validate([
            'historyIndex' => 'nullable|integer',
            'history_index' => 'nullable|integer',
            'historyIndexes' => 'nullable|array',
            'status' => 'required|string|in:Pending,Validated,Approved,For Revision,For Resubmission,Disapproved',
            'remarks' => 'nullable|string',
        ]);

        $status = $request->input('status');
        $remarks = $request->input('remarks', '');

        $indexes = $request->input('historyIndexes');
        if (empty($indexes)) {
            $singleIndex = $request->input('historyIndex', $request->input('history_index'));
            $indexes = $singleIndex !== null ? [$singleIndex] : [];
        }

        $history = $ecesproScholar->requirements_history ?? [];
        foreach ($indexes as $index) {
            if (isset($history[$index])) {
                $history[$index]['status'] = $status;
                $history[$index]['remarks'] = $remarks;
                $history[$index]['reviewed_at'] = now()->toIso8601String();
            }
        }

        $ecesproScholar->requirements_history = $history;
        $ecesproScholar->compliance_status = $status;
        $ecesproScholar->save();

        if (in_array($status, ['For Revision', 'For Resubmission'])) {
            if ($user = $ecesproScholar->user) {
                $reasonText = ! empty($remarks) ? " Reason: {$remarks}" : '';
                $customMsg = "Your semester compliance submission requires revision.{$reasonText}";

                if ($ecesproScholar->application) {
                    $user->notify(new EcesproApplicationStatusNotification($ecesproScholar->application, 'For Revision', $customMsg));
                }
            }
        }

        return response()->json([
            'message' => 'Compliance requirement updated successfully.',
            'scholar' => $ecesproScholar->load(['user', 'application']),
        ]);
    }

    /**
     * Get all volunteer logs for a specific scholar.
     */
    public function volunteerLogs(EcesproScholar $ecesproScholar)
    {
        $logs = $ecesproScholar->volunteerLogs()
            ->with(['event', 'verifiedBy'])
            ->latest('time_in')
            ->get();

        return response()->json([
            'scholar_id' => $ecesproScholar->id,
            'required_volunteer_hours' => (float) ($ecesproScholar->required_volunteer_hours ?: 30.00),
            'total_rendered_hours' => (float) ($ecesproScholar->total_rendered_hours ?: 0.00),
            'is_volunteer_completed' => (bool) $ecesproScholar->is_volunteer_completed,
            'logs' => $logs,
        ]);
    }

    /**
     * Store/Manually log volunteer hours for a scholar.
     */
    public function storeVolunteerLog(Request $request, EcesproScholar $ecesproScholar)
    {
        $validated = $request->validate([
            'activity_type' => 'required|string|in:event,office_duty,community_service,special_task',
            'duty_title' => 'required|string|max:255',
            'event_id' => 'nullable|exists:events,id',
            'time_in' => 'nullable|date',
            'time_out' => 'nullable|date',
            'hours_rendered' => 'required|numeric|min:0',
            'semester_period' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
        ]);

        $validated['scholar_id'] = $ecesproScholar->id;
        $validated['verified_by_user_id'] = $request->user()?->id;

        $log = EcesproVolunteerLog::create($validated);

        $ecesproScholar->recalculateVolunteerHours();
        $ecesproScholar->refresh();

        return response()->json([
            'message' => 'Volunteer log added successfully.',
            'log' => $log->load(['event', 'verifiedBy']),
            'scholar' => $ecesproScholar,
        ], 201);
    }

    /**
     * Delete a volunteer log for a scholar.
     */
    public function deleteVolunteerLog(EcesproScholar $ecesproScholar, $logId)
    {
        $log = $ecesproScholar->volunteerLogs()->findOrFail($logId);
        $log->delete();

        $ecesproScholar->recalculateVolunteerHours();
        $ecesproScholar->refresh();

        return response()->json([
            'message' => 'Volunteer log deleted successfully.',
            'scholar' => $ecesproScholar,
        ]);
    }
}
