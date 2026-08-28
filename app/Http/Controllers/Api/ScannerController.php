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
use Illuminate\Support\Facades\Auth;
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

        // 2. Ongoing Barangay / Official Events (Scoped to operator)
        $eventsQuery = Event::whereIn('status', ['ongoing', 'Ongoing', 'ONGOING']);
        if ($user) {
            $eventsQuery->where('user_id', $user->id);
        }
        $events = $eventsQuery->latest()->limit(20)->get();
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

        // 3. Ongoing Barangay / City Sports Programs (Scoped to operator)
        $sportsQuery = SportsProgram::query()->whereIn('status', ['ongoing', 'Ongoing', 'ONGOING']);
        if ($user) {
            $sportsQuery->where('user_id', $user->id);
        }
        $sports = $sportsQuery->latest()->limit(20)->get();
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
     * Get recent scanner attendance logs for the live audit feed.
     */
    public function recentLogs(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user() ?? $request->user();

        $query = AttendanceLog::with(['user.scholar', 'user.youthProfile', 'event', 'sportsProgram'])
            ->where(function ($q) use ($user) {
                $q->where('scanned_by_user_id', $user->id)
                    ->orWhereHas('event', fn ($eq) => $eq->where('user_id', $user->id))
                    ->orWhereHas('sportsProgram', fn ($sq) => $sq->where('user_id', $user->id));
            })
            ->latest('updated_at')
            ->limit(50);

        $logs = $query->get()->map(function (AttendanceLog $log) {
            $attendee = $log->user;
            $isScholar = $attendee?->scholar !== null;
            $roleLabel = $isScholar ? 'Scholar' : ($attendee?->role === UserRole::SkAdmin || $attendee?->role === 'sk_admin' ? 'SK Official' : 'Youth');

            $scanType = match ($log->status) {
                'timed_in' => 'time_in',
                'timed_out' => 'time_out',
                default => 'attendance_only',
            };

            $minutesRendered = null;
            $hoursRendered = null;
            if ($log->time_in && $log->time_out) {
                $minutesRendered = max(1, (int) $log->time_in->diffInMinutes($log->time_out));
                $hoursRendered = round($minutesRendered / 60, 2);
            }

            $timePoint = $log->time_out ?? $log->time_in ?? $log->updated_at ?? $log->created_at;

            return [
                'id' => $log->id,
                'attendee_name' => $attendee?->name ?? 'Unknown Attendee',
                'is_scholar' => $isScholar,
                'role' => $roleLabel,
                'duty_title' => $log->activity_title ?? ($log->event?->name ?? $log->sportsProgram?->name ?? 'General Attendance'),
                'scan_type' => $scanType,
                'status' => $log->status,
                'time_in' => $log->time_in?->toIso8601String(),
                'time_out' => $log->time_out?->toIso8601String(),
                'hours_rendered' => $hoursRendered,
                'minutes_rendered' => $minutesRendered,
                'timeString' => $timePoint ? $timePoint->timezone('Asia/Manila')->format('h:i:s A') : 'Recently',
                'created_at' => $log->created_at?->toIso8601String(),
                'updated_at' => $log->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $logs,
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

        // 1. Resolve scanned attendee with active scholar profile
        $attendee = User::with(['scholar' => fn ($q) => $q->where('status', 'Active')])
            ->where('qr_code_token', $validated['qr_code_token'])
            ->first();

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
                $activityType = 'sports';
                $eventName = $validated['duty_title'] ?? SportsProgram::where('id', $numericSportsProgramId)->value('name');
            } elseif (is_numeric($rawEventId)) {
                if ($activityType === 'sports') {
                    $numericSportsProgramId = (int) $rawEventId;
                    $eventName = $validated['duty_title'] ?? SportsProgram::where('id', $numericSportsProgramId)->value('name');
                } else {
                    $numericEventId = (int) $rawEventId;
                    $eventName = $validated['duty_title'] ?? Event::where('id', $numericEventId)->value('name');
                    $activityType = 'event';
                }
            }
        } elseif (empty($validated['activity_type'])) {
            $activityType = 'office_duty';
        }

        $activityTitle = $validated['duty_title'] ?? ($eventName ?: ($activityType === 'office_duty' ? 'TCYDO In-Office Duty' : 'Youth Activity Attendance'));

        // 2. Check if the attendee is an active ECESPRO Scholar
        $scholar = $attendee->scholar;

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
            $diffMinutes = max(1, (int) $timeIn->diffInMinutes($now));
            $hours = round($diffMinutes / 60, 2);
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

            $hoursFormatted = number_format($hours, 2);

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
                'minutes_rendered' => $diffMinutes,
                'total_rendered_hours' => (float) $scholar->total_rendered_hours,
                'required_volunteer_hours' => (float) ($scholar->required_volunteer_hours ?: EcesproSetting::get('required_volunteer_hours', 36.00)),
                'is_volunteer_completed' => (bool) $scholar->is_volunteer_completed,
                'message' => "🔴 Time-Out recorded for Scholar {$attendee->name}! (+{$hoursFormatted} hrs rendered)",
            ]);
        });
    }
}
