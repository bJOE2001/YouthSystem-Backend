<?php

namespace App\Actions\Youth\Dashboard;

use App\Enums\UserRole;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\SkOfficial;
use App\Models\SportsProgram;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetYouthDashboardAction
{
    public function handle(): array
    {
        $user = auth()->user();

        // 1. Check if youth user is overage (31+)
        $isOverage = false;
        $youthProfile = $user->youthProfile;
        if ($youthProfile && $youthProfile->birth_date) {
            $age = Carbon::parse($youthProfile->birth_date)->age;
            if ($age >= 31) {
                $isOverage = true;
            }
        }

        // 2. Calculate Cards
        $eventJoined = ($isOverage ? 0 : $user->joinedEvents()->count()) + $user->joinedSportsPrograms()->count();

        // Count earned certificates from events (if not overage):
        $eventCerts = 0;
        if (! $isOverage) {
            $eventCerts = $user->joinedEvents()
                ->wherePivotNotNull('attended_at')
                ->whereNotNull('events.certificate_template_path')
                ->where('events.certificate_template_path', '!=', '')
                ->distinct('events.id')
                ->count('events.id');
        }

        // Count earned certificates from sports programs:
        // a. Direct attended sports programs with certificate template
        $sportsWithCert = $user->joinedSportsPrograms()
            ->wherePivotNotNull('attended_at')
            ->whereNotNull('sports_programs.certificate_template_path')
            ->where('sports_programs.certificate_template_path', '!=', '')
            ->pluck('sports_programs.id')
            ->toArray();

        // b. Also check if user is in roster of any sports program that has a certificate template and was marked attended
        $allSportsWithCerts = SportsProgram::whereNotNull('certificate_template_path')
            ->where('certificate_template_path', '!=', '')
            ->get();

        foreach ($allSportsWithCerts as $sport) {
            if (in_array($sport->id, $sportsWithCert)) {
                continue;
            }

            $pivots = DB::table('sports_program_user')
                ->where('sports_program_id', $sport->id)
                ->get();

            foreach ($pivots as $pivot) {
                if (empty($pivot->teammates)) {
                    continue;
                }
                $roster = is_string($pivot->teammates) ? json_decode($pivot->teammates, true) : $pivot->teammates;
                if (! is_array($roster)) {
                    continue;
                }

                foreach ($roster as $m) {
                    $mUserId = $m['user_id'] ?? null;
                    $mName = $m['name'] ?? '';
                    $isUser = ($mUserId && $mUserId == $user->id) || (! empty($user->name) && strcasecmp(trim($mName), trim($user->name)) === 0);
                    $attended = ! empty($m['attended_at']) || ($m['status'] ?? '') === 'Attended';

                    if ($isUser && $attended) {
                        $sportsWithCert[] = $sport->id;
                        break 2;
                    }
                }
            }
        }

        $certificateEarned = $eventCerts + count(array_unique($sportsWithCert));

        $readAnnouncementIds = $user->readAnnouncements()->pluck('announcements.id');
        $unreadAnnouncements = Announcement::whereNotIn('id', $readAnnouncementIds)->count();

        $upcomingEventsCount = ($isOverage ? 0 : $user->joinedEvents()
            ->whereIn('events.status', ['upcoming', 'Upcoming'])
            ->count())
            + $user->joinedSportsPrograms()
                ->whereIn('sports_programs.status', ['upcoming', 'Upcoming'])
                ->count();

        // 2. Fetch Latest Events & Sports Programs Combined
        $userBarangay = $user->youthProfile->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;

        $latestEvents = collect();
        if (! $isOverage) {
            $eventsQuery = Event::query();
            $eventsQuery->where(function ($q) use ($userBarangay) {
                $q->where('open_to_all_barangays', true);
                $q->orWhereHas('user', function ($uq) {
                    $uq->whereIn('role', ['admin', UserRole::Admin->value]);
                });
                if ($userBarangay) {
                    $q->orWhereRaw('LOWER(barangay) = ?', [strtolower(trim($userBarangay))]);
                }
            });

            $latestEvents = $eventsQuery->latest()->take(5)->get()->map(function ($event) use ($user) {
                $start = Carbon::parse($event->start_date);

                return [
                    'id' => 'event_'.$event->id,
                    'name' => $event->name,
                    'source' => 'Event',
                    'ppa_classification' => $event->ppa_classification,
                    'ppaClassification' => $event->ppa_classification,
                    'description' => $event->ppa_classification ?: str($event->performance_indicator)->limit(100)->toString(),
                    'date' => $start->format('M d, Y').($event->start_time ? ' at '.Carbon::parse($event->start_time)->format('g:i A') : ''),
                    'location' => $event->location,
                    'status' => $event->status,
                    'joined' => $event->participants()->where('user_id', $user->id)->exists(),
                    'created_at' => $event->created_at,
                ];
            });
        }

        $sportsQuery = SportsProgram::query();
        $sportsQuery->where(function ($q) use ($userBarangay) {
            $q->where('open_to_all_barangays', true);
            if ($userBarangay) {
                $q->orWhereRaw('LOWER(barangay) = ?', [strtolower(trim($userBarangay))]);
            }
        });

        $latestSports = $sportsQuery->latest()->take(5)->get()->map(function ($sport) use ($user) {
            $start = Carbon::parse($sport->start_date);

            return [
                'id' => 'sport_'.$sport->id,
                'name' => $sport->name,
                'source' => 'Sports Program',
                'type' => $sport->type,
                'ppa_type' => $sport->type,
                'ppa_classification' => $sport->type,
                'ppaClassification' => $sport->type,
                'description' => $sport->type ?: str($sport->objective_1)->limit(100)->toString(),
                'date' => $start->format('M d, Y').($sport->start_time ? ' at '.Carbon::parse($sport->start_time)->format('g:i A') : ''),
                'location' => $sport->location,
                'status' => $sport->status,
                'joined' => $sport->participants()->where('user_id', $user->id)->exists(),
                'created_at' => $sport->created_at,
            ];
        });

        $events = collect($latestSports);
        if (! $isOverage) {
            $events = $events->merge($latestEvents);
        }

        $events = $events->sortByDesc('created_at')
            ->take(5)
            ->values()
            ->toArray();

        // 3. Fetch Latest Announcements
        $announcements = AnnouncementResource::collection(
            Announcement::latest()->take(5)->get()
        )->resolve();

        return [
            'cards' => [
                'eventJoined' => $eventJoined,
                'certificateEarnd' => $certificateEarned, // Matches frontend typo expectations
                'certificateEarned' => $certificateEarned,
                'undreadAnnouncements' => $unreadAnnouncements, // Matches frontend typo expectations
                'unreadAnnouncements' => $unreadAnnouncements,
                'upcomingEvents' => $upcomingEventsCount,
            ],
            'events' => $events,
            'announcements' => $announcements,
        ];
    }
}
