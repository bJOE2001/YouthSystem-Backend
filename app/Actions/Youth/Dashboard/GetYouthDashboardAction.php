<?php

namespace App\Actions\Youth\Dashboard;

use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\SportsProgram;
use Carbon\Carbon;

class GetYouthDashboardAction
{
    public function handle(): array
    {
        $user = auth()->user();

        // 1. Calculate Cards
        $eventJoined = $user->joinedEvents()->count() + $user->joinedSportsPrograms()->count();
        $certificateEarned = 0; // Placeholder for future feature
        $unreadAnnouncements = Announcement::count(); // Approximation since read receipts aren't tracked

        $upcomingEventsCount = Event::where('status', 'upcoming')->count()
            + SportsProgram::where('status', 'upcoming')->count();

        // 2. Fetch Latest Events & Sports Programs Combined
        $latestEvents = Event::latest()->take(5)->get()->map(function ($event) use ($user) {
            $start = Carbon::parse($event->start_date);

            return [
                'id' => 'event_'.$event->id,
                'name' => $event->name,
                'source' => 'Event',
                'description' => str($event->performance_indicator)->limit(100)->toString(),
                'date' => $start->format('M d, Y').($event->start_time ? ' at '.Carbon::parse($event->start_time)->format('g:i A') : ''),
                'location' => $event->location,
                'joined' => $event->participants()->where('user_id', $user->id)->exists(),
                'created_at' => $event->created_at,
            ];
        });

        $latestSports = SportsProgram::latest()->take(5)->get()->map(function ($sport) use ($user) {
            $start = Carbon::parse($sport->start_date);

            return [
                'id' => 'sport_'.$sport->id,
                'name' => $sport->name,
                'source' => 'Sports Program',
                'description' => str($sport->objective_1)->limit(100)->toString(),
                'date' => $start->format('M d, Y').($sport->start_time ? ' at '.Carbon::parse($sport->start_time)->format('g:i A') : ''),
                'location' => $sport->location,
                'joined' => $sport->participants()->where('user_id', $user->id)->exists(),
                'created_at' => $sport->created_at,
            ];
        });

        $events = collect($latestEvents)->merge($latestSports)
            ->sortByDesc('created_at')
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
                'undreadAnnouncements' => $unreadAnnouncements, // Matches frontend typo expectations
                'upcomingEvents' => $upcomingEventsCount,
            ],
            'events' => $events,
            'announcements' => $announcements,
        ];
    }
}
