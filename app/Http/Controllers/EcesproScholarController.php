<?php

namespace App\Http\Controllers;

use App\Models\EcesproScholar;
use App\Models\EcesproSetting;
use App\Models\EcesproVolunteerLog;
use App\Notifications\EcesproApplicationStatusNotification;
use Carbon\Carbon;
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
            'required_volunteer_hours' => 'nullable|numeric|min:0|max:500',
        ]);

        $scholar = EcesproScholar::create($validated);
        $scholar->recalculateVolunteerHours();

        return $scholar;
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
            'required_volunteer_hours' => 'nullable|numeric|min:0|max:500',
        ]);

        $ecesproScholar->update($validated);
        $ecesproScholar->recalculateVolunteerHours();
        $ecesproScholar->refresh();

        return $ecesproScholar->load(['user.youthProfile', 'application']);
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
                    'is_volunteer_completed' => (bool) $scholar->is_volunteer_completed,
                    'total_rendered_hours' => (float) ($scholar->total_rendered_hours ?: 0.00),
                    'required_volunteer_hours' => (float) $scholar->effective_required_volunteer_hours,
                    'volunteer_progress_percentage' => (float) $scholar->progress_percentage,
                    'remaining_volunteer_hours' => (float) $scholar->remaining_hours,
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
    public function volunteerLogs(Request $request, EcesproScholar $ecesproScholar)
    {
        $defaultRequired = (float) EcesproSetting::get('required_volunteer_hours', 36.00);
        $effectiveRequired = (float) ($ecesproScholar->required_volunteer_hours ?: $defaultRequired);
        $totalRendered = (float) ($ecesproScholar->total_rendered_hours ?: 0.00);
        $remaining = max(0, round($effectiveRequired - $totalRendered, 2));
        $percentage = $effectiveRequired > 0 ? min(100, round(($totalRendered / $effectiveRequired) * 100, 1)) : 100;

        $allLogs = $ecesproScholar->volunteerLogs()
            ->with(['event', 'verifiedBy'])
            ->latest('time_in')
            ->get();

        $semesterSummaries = $allLogs->groupBy(fn ($log) => $log->semester_period ?: 'Unassigned Semester')
            ->map(function ($group, $sem) use ($effectiveRequired) {
                $rendered = (float) $group->sum('hours_rendered');
                $remaining = max(0, round($effectiveRequired - $rendered, 2));
                $percent = $effectiveRequired > 0 ? min(100, round(($rendered / $effectiveRequired) * 100, 1)) : 100;

                return [
                    'semester_period' => $sem,
                    'rendered_hours' => $rendered,
                    'required_hours' => $effectiveRequired,
                    'remaining_hours' => $remaining,
                    'progress_percentage' => $percent,
                    'is_completed' => $rendered >= $effectiveRequired,
                    'logs_count' => $group->count(),
                ];
            })->values();

        $filteredLogs = $allLogs;
        if ($semFilter = $request->query('semester_period')) {
            $filteredLogs = $allLogs->filter(fn ($l) => $l->semester_period === $semFilter)->values();
        }

        $logsData = $filteredLogs->map(function ($log) {
            return [
                'id' => $log->id,
                'scholar_id' => $log->scholar_id,
                'event_id' => $log->event_id,
                'event_name' => $log->event?->name,
                'activity_type' => $log->activity_type,
                'duty_title' => $log->duty_title,
                'time_in' => $log->time_in?->toIso8601String(),
                'time_out' => $log->time_out?->toIso8601String(),
                'time_in_formatted' => $log->time_in?->format('M d, Y g:i A'),
                'time_out_formatted' => $log->time_out?->format('M d, Y g:i A'),
                'hours_rendered' => (float) $log->hours_rendered,
                'semester_period' => $log->semester_period,
                'verified_by_user_id' => $log->verified_by_user_id,
                'verified_by' => $log->verifiedBy?->name,
                'remarks' => $log->remarks,
                'status' => $log->time_out ? 'Completed' : 'Active (Timed In)',
                'created_at' => $log->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'scholar_id' => $ecesproScholar->id,
            'scholar_no' => $ecesproScholar->scholar_no,
            'required_volunteer_hours' => $effectiveRequired,
            'required_hours' => $effectiveRequired,
            'total_rendered_hours' => $totalRendered,
            'rendered_hours' => $totalRendered,
            'remaining_hours' => $remaining,
            'progress_percentage' => $percentage,
            'is_volunteer_completed' => (bool) $ecesproScholar->is_volunteer_completed,
            'is_completed' => (bool) $ecesproScholar->is_volunteer_completed,
            'has_override' => $ecesproScholar->required_volunteer_hours !== null,
            'override_hours' => $ecesproScholar->required_volunteer_hours ? (float) $ecesproScholar->required_volunteer_hours : null,
            'semester_summaries' => $semesterSummaries,
            'logs' => $logsData,
        ]);
    }

    /**
     * Store/Manually log volunteer hours for a scholar.
     */
    public function storeVolunteerLog(Request $request, EcesproScholar $ecesproScholar)
    {
        $validated = $request->validate([
            'activity_type' => 'required|string|in:event,office_duty,community_service,special_task,event_attendance',
            'duty_title' => 'required|string|max:255',
            'event_id' => 'nullable|exists:events,id',
            'time_in' => 'nullable|date',
            'time_out' => 'nullable|date',
            'hours_rendered' => 'nullable|numeric|min:0',
            'semester_period' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
        ]);

        if ($validated['activity_type'] === 'event_attendance') {
            $validated['activity_type'] = 'event';
        }

        $timeIn = ! empty($validated['time_in']) ? Carbon::parse($validated['time_in']) : now();
        $timeOut = ! empty($validated['time_out']) ? Carbon::parse($validated['time_out']) : null;

        $hoursRendered = isset($validated['hours_rendered']) && $validated['hours_rendered'] !== ''
            ? (float) $validated['hours_rendered']
            : ($timeOut ? round($timeIn->diffInMinutes($timeOut) / 60, 2) : 0.00);

        $validated['scholar_id'] = $ecesproScholar->id;
        $validated['time_in'] = $timeIn;
        $validated['time_out'] = $timeOut;
        $validated['hours_rendered'] = $hoursRendered;
        $validated['verified_by_user_id'] = $request->user()?->id;

        $log = EcesproVolunteerLog::create($validated);

        $ecesproScholar->recalculateVolunteerHours();
        $ecesproScholar->refresh();

        return response()->json([
            'message' => 'Volunteer log added successfully.',
            'log' => $log->load(['event', 'verifiedBy']),
            'scholar' => $ecesproScholar->load(['user.youthProfile', 'application']),
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
            'scholar' => $ecesproScholar->load(['user.youthProfile', 'application']),
        ]);
    }
}
