<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventParticipantResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query();
        $user = auth()->user();

        $isOwnerRequest = $request->input('owner') === 'me';

        if ($isOwnerRequest && ($user->role === UserRole::Admin || $user->role === UserRole::SkAdmin)) {
            $query->where('user_id', $user->id);
        } else {
            // For youths, or when viewing all available events, show published events
            $query->whereIn('status', ['upcoming', 'ongoing']);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('location', 'like', '%'.$request->search.'%')
                    ->orWhere('ppa_classification', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('sort_by') && ! empty($request->sort_by)) {
            $sortBy = Str::snake($request->sort_by);
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $perPage = $request->input('per_page', 10);

        return EventResource::collection($query->paginate($perPage));
    }

    public function store(StoreEventRequest $request)
    {
        $data = $this->mapToSnakeCase($request->validated());
        $data['user_id'] = auth()->id();
        $event = Event::create($data);

        return new EventResource($event);
    }

    public function show(Event $event)
    {
        return new EventResource($event);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $this->mapToSnakeCase($request->validated());
        $event->update($data);

        return new EventResource($event);
    }

    public function updateStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|string|in:draft,upcoming,ongoing,completed,cancelled',
        ]);

        $event->update(['status' => $request->status]);

        return new EventResource($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->noContent();
    }

    public function join(Event $event)
    {
        $user = auth()->user();

        if ($user->joinedEvents()->where('event_id', $event->id)->exists()) {
            return response()->json(['message' => 'Already joined this event.'], 400);
        }

        $user->joinedEvents()->attach($event->id);

        return new EventResource($event);
    }

    public function myEvents(Request $request)
    {
        $user = auth()->user();

        $query = $user->joinedEvents();

        if ($request->has('search') && ! empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('location', 'like', '%'.$request->search.'%')
                    ->orWhere('ppa_classification', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('sort_by') && ! empty($request->sort_by)) {
            $sortBy = Str::snake($request->sort_by);
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $perPage = $request->input('per_page', 10);

        return EventResource::collection($query->paginate($perPage));
    }

    public function participants(Request $request, Event $event)
    {
        $query = $event->participants()->with('youthProfile');

        $perPage = $request->input('per_page', 10);

        return EventParticipantResource::collection($query->paginate($perPage));
    }

    public function markAttendance(Request $request, Event $event, User $user)
    {
        // Check if the user is a participant
        if (! $event->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User is not a participant of this event.'], 400);
        }

        // Update the pivot table with current timestamp
        $event->participants()->updateExistingPivot($user->id, [
            'attended_at' => now(),
        ]);

        return response()->json(['message' => 'Attendance marked successfully.']);
    }

    public function attendanceLogs(Request $request, Event $event)
    {
        // Only get participants who have attended, ordered by attendance time
        $query = $event->participants()
            ->with('youthProfile')
            ->whereNotNull('event_user.attended_at')
            ->orderBy('event_user.attended_at', 'desc');

        $perPage = $request->input('per_page', 10);

        return EventParticipantResource::collection($query->paginate($perPage));
    }

    private function mapToSnakeCase(array $data): array
    {
        $mapped = [];
        foreach ($data as $key => $value) {
            $snakeKey = Str::snake($key);

            // Fix for primaryObjective1 -> primary_objective_1
            if (preg_match('/^primary_objective(\d+)$/', $snakeKey, $matches)) {
                $snakeKey = 'primary_objective_'.$matches[1];
            }

            $mapped[$snakeKey] = $value;
        }

        return $mapped;
    }
}
