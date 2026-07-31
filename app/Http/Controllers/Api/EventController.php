<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventParticipantResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\UnifiedEventResource;
use App\Models\Event;
use App\Models\SkOfficial;
use App\Models\SportsProgram;
use App\Models\User;
use App\Notifications\NewEventNotification;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();
        $isOwnerRequest = $request->input('owner') === 'me';

        if ($isOwnerRequest && $user && ($user->role === UserRole::Admin || $user->role === UserRole::SkAdmin)) {
            $query = Event::where('user_id', $user->id);
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

        // Unified youth view
        $userBarangay = null;
        if ($user) {
            $userBarangay = $user->youthProfile->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;
        }

        $events = Event::whereIn('status', ['upcoming', 'ongoing', 'Upcoming', 'Ongoing']);
        $sports = SportsProgram::whereIn('status', ['upcoming', 'ongoing', 'Upcoming', 'Ongoing']);

        if (! $user || $user->role !== UserRole::Admin) {
            $sports->where(function ($q) use ($userBarangay) {
                $q->where('open_to_all_barangays', true);
                if ($userBarangay) {
                    $q->orWhereRaw('LOWER(barangay) = ?', [strtolower(trim($userBarangay))]);
                }
            });
        }

        if ($request->has('search') && ! empty($request->search)) {
            $search = '%'.$request->search.'%';
            $events->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('location', 'like', $search)
                    ->orWhere('ppa_classification', 'like', $search);
            });
            $sports->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('location', 'like', $search)
                    ->orWhere('type', 'like', $search);
            });
        }

        $all = $events->get()->concat($sports->get());

        if ($request->has('sort_by') && ! empty($request->sort_by)) {
            $sortBy = Str::camel($request->sort_by);
            $sortOrder = $request->input('sort_order', 'asc');
            $all = $sortOrder === 'desc' ? $all->sortByDesc($sortBy) : $all->sortBy($sortBy);
        } else {
            $all = $all->sortByDesc('created_at');
        }

        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);

        $paginated = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return UnifiedEventResource::collection($paginated);
    }

    public function store(StoreEventRequest $request)
    {
        $data = $this->mapToSnakeCase($request->validated());
        $data['user_id'] = auth()->id();
        $event = Event::create($data);

        $youthUsers = User::where('role', 'youth')->get();
        Notification::send($youthUsers, new NewEventNotification($event));

        return new EventResource($event);
    }

    public function show($id)
    {
        if (Str::startsWith($id, 'sport_')) {
            $sportId = str_replace('sport_', '', $id);
            $sport = SportsProgram::findOrFail($sportId);

            return new UnifiedEventResource($sport);
        }

        $eventId = str_replace('event_', '', $id);
        $event = Event::findOrFail($eventId);

        return new UnifiedEventResource($event);
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

    public function join($id)
    {
        $user = auth()->user();

        if (Str::startsWith($id, 'sport_')) {
            $sportId = str_replace('sport_', '', $id);
            $sport = SportsProgram::findOrFail($sportId);

            if (! $sport->open_to_all_barangays) {
                $userBarangay = $user->youthProfile->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;
                if (! $userBarangay || strtolower(trim($userBarangay)) !== strtolower(trim($sport->barangay))) {
                    return response()->json(['message' => 'This sports program is exclusive to residents of Barangay '.$sport->barangay.'.'], 403);
                }
            }

            if ($user->joinedSportsPrograms()->where('sports_program_id', $sport->id)->exists()) {
                return response()->json(['message' => 'Already joined this program.'], 400);
            }

            $user->joinedSportsPrograms()->attach($sport->id);

            return new UnifiedEventResource($sport);
        }

        $eventId = str_replace('event_', '', $id);
        $event = Event::findOrFail($eventId);

        if ($user->joinedEvents()->where('event_id', $event->id)->exists()) {
            return response()->json(['message' => 'Already joined this event.'], 400);
        }

        $user->joinedEvents()->attach($event->id);

        return new UnifiedEventResource($event);
    }

    public function myEvents(Request $request)
    {
        $user = auth()->user();

        $events = $user->joinedEvents();
        $sports = $user->joinedSportsPrograms();

        if ($request->has('search') && ! empty($request->search)) {
            $search = '%'.$request->search.'%';
            $events->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('location', 'like', $search)
                    ->orWhere('ppa_classification', 'like', $search);
            });
            $sports->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('location', 'like', $search)
                    ->orWhere('type', 'like', $search);
            });
        }

        $all = $events->get()->concat($sports->get());

        if ($request->has('sort_by') && ! empty($request->sort_by)) {
            $sortBy = Str::camel($request->sort_by);
            $sortOrder = $request->input('sort_order', 'asc');
            $all = $sortOrder === 'desc' ? $all->sortByDesc($sortBy) : $all->sortBy($sortBy);
        } else {
            $all = $all->sortByDesc('created_at');
        }

        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);

        $paginated = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return UnifiedEventResource::collection($paginated);
    }

    public function participants(Request $request, $id)
    {
        if (Str::startsWith($id, 'sport_')) {
            $sportId = str_replace('sport_', '', $id);
            $model = SportsProgram::findOrFail($sportId);
        } else {
            $eventId = str_replace('event_', '', $id);
            $model = Event::findOrFail($eventId);
        }

        $query = $model->participants()->with('youthProfile');

        $perPage = $request->input('per_page', 10);

        return EventParticipantResource::collection($query->paginate($perPage));
    }

    public function markAttendance(Request $request, $id, User $user)
    {
        if (Str::startsWith($id, 'sport_')) {
            $sportId = str_replace('sport_', '', $id);
            $model = SportsProgram::findOrFail($sportId);
        } else {
            $eventId = str_replace('event_', '', $id);
            $model = Event::findOrFail($eventId);
        }

        // Check if the user is a participant
        if (! $model->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User is not a participant of this event/program.'], 400);
        }

        // Update the pivot table with current timestamp
        $model->participants()->updateExistingPivot($user->id, [
            'attended_at' => now(),
        ]);

        return response()->json(['message' => 'Attendance marked successfully.']);
    }

    public function attendanceLogs(Request $request, $id)
    {
        if (Str::startsWith($id, 'sport_')) {
            $sportId = str_replace('sport_', '', $id);
            $model = SportsProgram::findOrFail($sportId);
            $pivotColumn = 'sports_program_user.attended_at';
        } else {
            $eventId = str_replace('event_', '', $id);
            $model = Event::findOrFail($eventId);
            $pivotColumn = 'event_user.attended_at';
        }

        // Only get participants who have attended, ordered by attendance time
        $query = $model->participants()
            ->with('youthProfile')
            ->whereNotNull($pivotColumn)
            ->orderBy($pivotColumn, 'desc');

        $perPage = $request->input('per_page', 10);

        return EventParticipantResource::collection($query->paginate($perPage));
    }

    public function downloadCertificate($id)
    {
        $user = auth()->user();
        if (Str::startsWith($id, 'sport_')) {
            $sportId = str_replace('sport_', '', $id);
            $event = SportsProgram::findOrFail($sportId);
        } else {
            $eventId = str_replace('event_', '', $id);
            $event = Event::findOrFail($eventId);
        }

        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>endobj 4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj 5 0 obj<</Length 140>>stream\nBT /F1 18 Tf 50 700 Td (CERTIFICATE OF PARTICIPATION) Tj /F1 12 Tf 0 -30 Td (This certifies that {$user->name} has completed the activity: {$event->name}.) Tj ET\nendstream\nendobj\nxref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000056 00000 n \n0000000111 00000 n \n0000000238 00000 n \n0000000305 00000 n \ntrailer<</Size 6/Root 1 0 R>>\nstartxref\n497\n%%EOF";

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Certificate-'.Str::slug($event->name).'.pdf"',
        ]);
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
