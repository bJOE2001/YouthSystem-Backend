<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\EcesproSetting;
use App\Models\EcesproVolunteerLog;
use App\Models\Event;
use App\Models\SportsProgram;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScannerController extends Controller
{
    /**
     * Get contextual attendance and volunteer duty activities for the scanner operator.
     */
    public function activities(Request $request): JsonResponse
    {
        $user = $request->user();
        $isSkAdmin = $user && ($user->role === UserRole::SkAdmin || $user->role === 'sk_admin' || $user->role?->value === 'sk_admin');

        $barangay = null;
        if ($isSkAdmin) {
            $user->loadMissing(['skOfficial', 'youthProfile']);
            $barangay = $user->skOfficial?->barangay ?? $user->youthProfile?->barangay ?? 'Barangay';
        }

        $activities = [];

        // 1. General Volunteer Duties (City Admin only)
        if (! $isSkAdmin) {
            $activities[] = [
                'value' => 'duty_tcydo_office',
                'event_id' => null,
                'activity_type' => 'office_duty',
                'duty_title' => 'TCYDO In-Office Duty',
                'label' => '🏛️ TCYDO In-Office Duty',
                'icon' => 'account_balance',
                'group' => 'TCYDO Volunteer Duties',
            ];
            $activities[] = [
                'value' => 'duty_city_community',
                'event_id' => null,
                'activity_type' => 'community_service',
                'duty_title' => 'City-Wide Community Service / Outreach',
                'label' => '🤝 City-Wide Community Outreach',
                'icon' => 'volunteer_activism',
                'group' => 'TCYDO Volunteer Duties',
            ];
            $activities[] = [
                'value' => 'duty_city_assembly',
                'event_id' => null,
                'activity_type' => 'event',
                'duty_title' => 'City Youth Assembly / Summit',
                'label' => '👥 City Youth Assembly / Summit',
                'icon' => 'groups',
                'group' => 'TCYDO Volunteer Duties',
            ];
        }

        // 2. Active Barangay / Official Events
        $eventsQuery = Event::where('status', '!=', 'cancelled');
        if ($isSkAdmin) {
            $eventsQuery->where('user_id', $user->id);
        }
        $events = $eventsQuery->latest()->limit(15)->get();
        foreach ($events as $event) {
            $activities[] = [
                'value' => "event_{$event->id}",
                'event_id' => $event->id,
                'activity_type' => 'event',
                'duty_title' => $event->name,
                'label' => "📅 {$event->name}",
                'icon' => 'event',
                'group' => $isSkAdmin ? 'Barangay Events' : 'Official Events',
            ];
        }

        // 3. Active Barangay / City Sports Programs
        $sportsQuery = SportsProgram::query()->where('status', '!=', 'cancelled');
        if ($isSkAdmin) {
            $sportsQuery->where('user_id', $user->id);
        }
        $sports = $sportsQuery->latest()->limit(15)->get();
        foreach ($sports as $sport) {
            $activities[] = [
                'value' => "sport_{$sport->id}",
                'event_id' => "sport_{$sport->id}",
                'activity_type' => 'sports',
                'duty_title' => $sport->name,
                'label' => "🏆 {$sport->name}",
                'icon' => 'sports_basketball',
                'group' => $isSkAdmin ? 'Barangay Sports Programs' : 'Sports Programs',
            ];
        }

        $roleStr = $user?->role instanceof UserRole ? $user->role->value : (string) $user?->role;

        return response()->json([
            'success' => true,
            'role' => $roleStr,
            'barangay' => $barangay,
            'data' => $activities,
        ]);
    }

    /**
     * Record QR scan for attendance or scholar volunteer hours across Events, Sports, and Office Duty.
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

        // Resolve activity classification (Event, Sports, or Office Duty)
        $rawEventId = $validated['event_id'] ?? null;
        $numericEventId = null;
        $numericSportsProgramId = null;
        $eventName = null;
        $activityType = $validated['activity_type'] ?? 'event';

        if ($rawEventId) {
            if (is_string($rawEventId) && str_starts_with($rawEventId, 'sport_')) {
                $numericSportsProgramId = (int) substr($rawEventId, 6);
                $sports = SportsProgram::find($numericSportsProgramId);
                $eventName = $sports ? $sports->name : ($validated['duty_title'] ?? 'Sports Program');
                $activityType = 'sports';
            } elseif (is_numeric($rawEventId)) {
                if ($activityType === 'sports') {
                    $numericSportsProgramId = (int) $rawEventId;
                    $sports = SportsProgram::find($numericSportsProgramId);
                    $eventName = $sports ? $sports->name : ($validated['duty_title'] ?? 'Sports Program');
                } else {
                    $numericEventId = (int) $rawEventId;
                    $evt = Event::find($numericEventId);
                    $eventName = $evt ? $evt->name : null;
                    $activityType = 'event';
                }
            }
        } elseif (empty($validated['activity_type'])) {
            $activityType = 'office_duty';
        }

        $activityTitle = $validated['duty_title'] ?? ($eventName ?: ($activityType === 'office_duty' ? 'TCYDO In-Office Duty' : 'Youth Activity Attendance'));

        // 2. Check if the attendee is an active ECESPRO Scholar
        $scholar = $attendee->scholar()->where('status', 'Active')->first();

        return DB::transaction(function () use (
            $attendee,
            $scholar,
            $official,
            $validated,
            $numericEventId,
            $numericSportsProgramId,
            $activityType,
            $activityTitle
        ) {
            $now = Carbon::now();

            if (! $scholar) {
                // ==========================================
                // SCENARIO A: Regular Youth / SK (1 SCAN ONLY)
                // ==========================================
                $attendance = AttendanceLog::updateOrCreate(
                    [
                        'user_id' => $attendee->id,
                        'event_id' => $numericEventId,
                        'sports_program_id' => $numericSportsProgramId,
                        'activity_type' => $activityType,
                    ],
                    [
                        'activity_title' => $activityTitle,
                        'time_in' => $now,
                        'status' => 'attended',
                        'scanned_by_user_id' => $official ? $official->id : null,
                        'remarks' => $validated['remarks'] ?? 'QR attendance recorded',
                    ]
                );

                if ($numericEventId) {
                    $attendee->joinedEvents()->syncWithoutDetaching([$numericEventId]);
                }
                if ($numericSportsProgramId) {
                    $attendee->joinedSportsPrograms()->syncWithoutDetaching([$numericSportsProgramId]);
                }

                $roleLabel = $attendee->role === 'sk_admin' ? 'SK Official' : 'Youth';

                return response()->json([
                    'success' => true,
                    'is_scholar' => false,
                    'scan_type' => 'attendance_only',
                    'attendee_name' => $attendee->name,
                    'role' => $roleLabel,
                    'status' => 'attended',
                    'activity_type' => $activityType,
                    'activity_title' => $activityTitle,
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
                $semesterPeriod = $validated['semester_period'] ?? ($scholar->application?->school_year ?? '2026-1st-Sem');

                $newLog = EcesproVolunteerLog::create([
                    'scholar_id' => $scholar->id,
                    'event_id' => $numericEventId,
                    'activity_type' => $activityType === 'sports' ? 'sports_program' : ($activityType === 'event' ? 'event_attendance' : 'office_duty'),
                    'duty_title' => $activityTitle,
                    'time_in' => $now,
                    'time_out' => null,
                    'hours_rendered' => 0.00,
                    'semester_period' => $semesterPeriod,
                    'verified_by_user_id' => $official ? $official->id : null,
                    'remarks' => $validated['remarks'] ?? null,
                ]);

                AttendanceLog::updateOrCreate(
                    [
                        'user_id' => $attendee->id,
                        'event_id' => $numericEventId,
                        'sports_program_id' => $numericSportsProgramId,
                        'activity_type' => $activityType,
                    ],
                    [
                        'activity_title' => $activityTitle,
                        'time_in' => $now,
                        'status' => 'timed_in',
                        'scanned_by_user_id' => $official ? $official->id : null,
                        'remarks' => "Time-In: {$activityTitle}",
                    ]
                );

                return response()->json([
                    'success' => true,
                    'is_scholar' => true,
                    'scan_type' => 'time_in',
                    'attendee_name' => $attendee->name,
                    'role' => 'Scholar',
                    'status' => 'timed_in',
                    'activity_type' => $activityType,
                    'duty_title' => $activityTitle,
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

            AttendanceLog::where('user_id', $attendee->id)
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
            if ($numericSportsProgramId) {
                $attendee->joinedSportsPrograms()->syncWithoutDetaching([$numericSportsProgramId]);
            }

            return response()->json([
                'success' => true,
                'is_scholar' => true,
                'scan_type' => 'time_out',
                'attendee_name' => $attendee->name,
                'role' => 'Scholar',
                'status' => 'timed_out',
                'activity_type' => $activityType,
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
