<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EcesproSetting;
use App\Models\EcesproVolunteerLog;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScannerController extends Controller
{
    /**
     * Record QR scan for attendance or scholar volunteer hours.
     */
    public function recordScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_code_token' => ['required', 'string'],
            'event_id' => ['nullable'],
            'activity_type' => ['nullable', 'string'],
            'duty_title' => ['nullable', 'string'],
            'semester_period' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $official = $request->user();

        // 1. Resolve scanned attendee by QR code token
        $attendee = User::where('qr_code_token', $validated['qr_code_token'])->first();
        if (! $attendee) {
            return response()->json([
                'success' => false,
                'message' => 'User not found for this QR code.',
            ], 404);
        }

        // Resolve event if provided (can be numeric ID or string like 'sport_1')
        $rawEventId = $validated['event_id'] ?? null;
        $numericEventId = null;
        $eventName = null;
        if ($rawEventId) {
            if (is_numeric($rawEventId)) {
                $numericEventId = (int) $rawEventId;
                $evt = Event::find($numericEventId);
                $eventName = $evt ? $evt->name : null;
            } elseif (is_string($rawEventId) && str_starts_with($rawEventId, 'sport_')) {
                $dutyTitle = $validated['duty_title'] ?? 'Sports Event';
                $eventName = $dutyTitle;
            }
        }

        // 2. Check if the attendee is an active ECESPRO Scholar
        $scholar = $attendee->scholar()->where('status', 'Active')->first();

        return DB::transaction(function () use ($attendee, $scholar, $official, $validated, $numericEventId, $eventName) {
            $now = Carbon::now();

            if (! $scholar) {
                // ==========================================
                // SCENARIO A: Regular Youth / SK (1 SCAN ONLY)
                // ==========================================
                $attendance = EventAttendance::updateOrCreate(
                    [
                        'user_id' => $attendee->id,
                        'event_id' => $numericEventId,
                    ],
                    [
                        'time_in' => $now,
                        'status' => 'attended',
                        'scanned_by_user_id' => $official ? $official->id : null,
                        'remarks' => $validated['remarks'] ?? 'QR attendance recorded',
                    ]
                );

                if ($numericEventId) {
                    $attendee->joinedEvents()->syncWithoutDetaching([$numericEventId]);
                }

                $roleLabel = $attendee->role === 'sk_admin' ? 'SK Official' : 'Youth';

                return response()->json([
                    'success' => true,
                    'is_scholar' => false,
                    'scan_type' => 'attendance_only',
                    'attendee_name' => $attendee->name,
                    'role' => $roleLabel,
                    'status' => 'attended',
                    'time_in' => $attendance->time_in->toIso8601String(),
                    'message' => "✅ Attendance recorded for {$attendee->name}!",
                ]);
            }

            // ==============================================
            // SCENARIO B: ECESPRO Scholar (2 SCANS: Time-In & Time-Out)
            // ==============================================
            $activeLog = EcesproVolunteerLog::where('scholar_id', $scholar->id)
                ->whereNull('time_out')
                ->latest('time_in')
                ->first();

            if (! $activeLog) {
                // ------------------------------------------
                // SCAN 1: Time-In (Arrival)
                // ------------------------------------------
                $activityType = $validated['activity_type'] ?? ($numericEventId ? 'event_attendance' : 'office_duty');
                $dutyTitle = $validated['duty_title'] ?? ($eventName ?: 'Office Duty / Volunteer Service');
                $semesterPeriod = $validated['semester_period'] ?? ($scholar->application?->school_year ?? '2026-1st-Sem');

                $newLog = EcesproVolunteerLog::create([
                    'scholar_id' => $scholar->id,
                    'event_id' => $numericEventId,
                    'activity_type' => $activityType,
                    'duty_title' => $dutyTitle,
                    'time_in' => $now,
                    'time_out' => null,
                    'hours_rendered' => 0.00,
                    'semester_period' => $semesterPeriod,
                    'verified_by_user_id' => $official ? $official->id : null,
                    'remarks' => $validated['remarks'] ?? null,
                ]);

                EventAttendance::updateOrCreate(
                    [
                        'user_id' => $attendee->id,
                        'event_id' => $numericEventId,
                    ],
                    [
                        'time_in' => $now,
                        'status' => 'timed_in',
                        'scanned_by_user_id' => $official ? $official->id : null,
                        'remarks' => "Time-In: {$dutyTitle}",
                    ]
                );

                return response()->json([
                    'success' => true,
                    'is_scholar' => true,
                    'scan_type' => 'time_in',
                    'attendee_name' => $attendee->name,
                    'role' => 'Scholar',
                    'status' => 'timed_in',
                    'duty_title' => $dutyTitle,
                    'time_in' => $newLog->time_in->toIso8601String(),
                    'total_rendered_hours' => (float) $scholar->total_rendered_hours,
                    'required_volunteer_hours' => (float) ($scholar->required_volunteer_hours ?: EcesproSetting::get('required_volunteer_hours', 36.00)),
                    'is_volunteer_completed' => (bool) $scholar->is_volunteer_completed,
                    'message' => "🟢 Time-In recorded for Scholar {$attendee->name}!",
                ]);
            }

            // ------------------------------------------
            // SCAN 2: Time-Out (Departure)
            // ------------------------------------------
            $timeIn = $activeLog->time_in;
            $hours = round($timeIn->diffInMinutes($now) / 60, 2);
            if ($hours < 0.01) {
                $hours = 0.01;
            }

            $activeLog->update([
                'time_out' => $now,
                'hours_rendered' => $hours,
            ]);

            $scholar->recalculateVolunteerHours();
            $scholar->refresh();

            EventAttendance::where('user_id', $attendee->id)
                ->where('status', 'timed_in')
                ->latest('time_in')
                ->first()
                ?->update([
                    'time_out' => $now,
                    'status' => 'timed_out',
                ]);

            if ($numericEventId) {
                $attendee->joinedEvents()->syncWithoutDetaching([$numericEventId]);
            }

            return response()->json([
                'success' => true,
                'is_scholar' => true,
                'scan_type' => 'time_out',
                'attendee_name' => $attendee->name,
                'role' => 'Scholar',
                'status' => 'timed_out',
                'duty_title' => $activeLog->duty_title,
                'time_in' => $activeLog->time_in->toIso8601String(),
                'time_out' => $now->toIso8601String(),
                'hours_rendered' => $hours,
                'total_rendered_hours' => (float) $scholar->total_rendered_hours,
                'required_volunteer_hours' => (float) ($scholar->required_volunteer_hours ?: EcesproSetting::get('required_volunteer_hours', 36.00)),
                'is_volunteer_completed' => (bool) $scholar->is_volunteer_completed,
                'message' => "🔴 Time-Out recorded for Scholar {$attendee->name}! ({$hours} hrs rendered)",
            ]);
        });
    }
}
