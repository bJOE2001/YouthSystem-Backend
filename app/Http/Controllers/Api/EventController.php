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
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function __construct(protected CertificateService $certificateService) {}

    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
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
            $events->where(function ($q) use ($userBarangay) {
                $q->where('open_to_all_barangays', true);
                
                // City-wide events created by City Admins are open to all
                $q->orWhereHas('user', function ($uq) {
                    $uq->where('role', UserRole::Admin->value)
                        ->orWhere('role', 'admin');
                });
                
                // Barangay events created by SK Admins are exclusive to youth belonging to that barangay
                if ($userBarangay) {
                    $q->orWhereHas('user', function ($uq) use ($userBarangay) {
                        $uq->where(function ($roleQ) {
                            $roleQ->where('role', UserRole::SkAdmin->value)
                                ->orWhere('role', 'sk_admin');
                        });
                    })->where(function ($bq) use ($userBarangay) {
                        $bq->whereRaw('LOWER(events.barangay) = ?', [strtolower(trim($userBarangay))]);
                    });
                }
            });

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
        /** @var User $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        $data = $this->mapToSnakeCase($request->validated());
        $data['user_id'] = Auth::id() ?? $request->user()?->id;
        
        $isOpenToAll = filter_var($request->input('openToAll', $request->input('openToAllBarangays', $request->input('open_to_all_barangays', false))), FILTER_VALIDATE_BOOLEAN);
        $data['open_to_all_barangays'] = $isOpenToAll;
        if (! $isOpenToAll) {
            $data['barangay'] = $user->youthProfile->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;
        } else {
            $data['barangay'] = null;
        }

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
        /** @var User $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        $data = $this->mapToSnakeCase($request->validated());
        
        if ($request->has('openToAll') || $request->has('openToAllBarangays') || $request->has('open_to_all_barangays')) {
            $isOpenToAll = filter_var($request->input('openToAll', $request->input('openToAllBarangays', $request->input('open_to_all_barangays', false))), FILTER_VALIDATE_BOOLEAN);
            $data['open_to_all_barangays'] = $isOpenToAll;
            if (! $isOpenToAll) {
                $data['barangay'] = $user->youthProfile->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;
            } else {
                $data['barangay'] = null;
            }
        }
        
        $event->update($data);

        return new EventResource($event);
    }

    public function updateStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|string|in:draft,upcoming,ongoing,completed,cancelled',
        ]);

        $event->update([
            'status' => $request->status,
        ]);

        return new EventResource($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully.']);
    }

    public function join($id)
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user();

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
        $event = Event::with(['user.skOfficial', 'user.youthProfile'])->findOrFail($eventId);

        $creator = $event->user;
        $isSkEvent = $creator && ($creator->role === UserRole::SkAdmin || $creator->role === 'sk_admin' || $creator->role?->value === 'sk_admin');
        if ($isSkEvent && !$event->open_to_all_barangays) {
            $eventBarangay = $creator->skOfficial?->barangay ?? $creator->youthProfile?->barangay;
            $userBarangay = $user->youthProfile?->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;
            if ($eventBarangay && (! $userBarangay || strtolower(trim($userBarangay)) !== strtolower(trim($eventBarangay)))) {
                return response()->json(['message' => 'This event is exclusive to residents of Barangay '.$eventBarangay.'.'], 403);
            }
        }

        if ($user->joinedEvents()->where('event_id', $event->id)->exists()) {
            return response()->json(['message' => 'Already joined this event.'], 400);
        }

        $user->joinedEvents()->attach($event->id);

        return new UnifiedEventResource($event);
    }

    public function myEvents(Request $request)
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user() ?? $request->user();

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

    public function markAttendance(Request $request, $id, $participant)
    {
        $model = $this->resolveActivityModel($id, $request);

        if ($model instanceof SportsProgram) {
            return $this->markSportsProgramAttendance($request, $model, $participant);
        }

        $user = is_numeric($participant) ? User::find($participant) : null;
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Check if the user is a participant
        if (! $model->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User is not a participant of this event/program.'], 400);
        }

        // Update the pivot table with current timestamp
        $pivotData = [
            'attended_at' => now(),
        ];
        if (! empty($model->certificate_template_path)) {
            $pivotTable = 'event_user';
            if (Schema::hasColumn($pivotTable, 'certificate_path')) {
                $pivotData['certificate_path'] = $model->certificate_template_path;
            }
        }

        $model->participants()->updateExistingPivot($user->id, $pivotData);

        return response()->json(['message' => 'Attendance marked successfully.']);
    }

    protected function markSportsProgramAttendance(Request $request, SportsProgram $sportsProgram, $participant)
    {
        $now = now();
        $marked = false;
        $participantStr = (string) $participant;
        $reqName = $request->input('name');
        $reqTeam = $request->input('team_name');
        $reqUserId = $request->input('user_id');
        $reqPartId = (string) ($request->input('participant_id') ?? '');

        // 1. Direct pivot update if $participant or $reqUserId is a numeric user_id
        $resolvedUserId = is_numeric($participant) ? (int) $participant : (is_numeric($reqUserId) ? (int) $reqUserId : null);
        if ($resolvedUserId) {
            $user = User::find($resolvedUserId);
            if ($user && $sportsProgram->participants()->where('user_id', $user->id)->exists()) {
                $pivotData = ['attended_at' => $now];
                if (! empty($sportsProgram->certificate_template_path) && Schema::hasColumn('sports_program_user', 'certificate_path')) {
                    $pivotData['certificate_path'] = $sportsProgram->certificate_template_path;
                }
                $sportsProgram->participants()->updateExistingPivot($user->id, $pivotData);
                $marked = true;
            }
        }

        // 2. Search and update teammate within roster JSON across all teams in this sports program
        $pivots = DB::table('sports_program_user')
            ->where('sports_program_id', $sportsProgram->id)
            ->get();

        foreach ($pivots as $pivot) {
            if (empty($pivot->teammates)) {
                continue;
            }

            $roster = is_string($pivot->teammates) ? json_decode($pivot->teammates, true) : $pivot->teammates;
            if (! is_array($roster)) {
                continue;
            }

            $rosterUpdated = false;
            foreach ($roster as &$member) {
                $mUserId = $member['user_id'] ?? null;
                $mName = $member['name'] ?? '';
                $tmGeneratedId = 'tm_'.md5($pivot->user_id.'_'.$mName);

                $isMatch = false;

                // Match by generated tm_ id
                if ($participantStr === $tmGeneratedId || $reqPartId === $tmGeneratedId) {
                    $isMatch = true;
                }
                // Match by explicit member id if present
                elseif (isset($member['id']) && ((string) $member['id'] === $participantStr || (string) $member['id'] === $reqPartId)) {
                    $isMatch = true;
                }
                // Match by user_id if numeric
                elseif ($resolvedUserId && $mUserId && (int) $mUserId === $resolvedUserId) {
                    $isMatch = true;
                }
                // Match by explicit name
                elseif (! empty($reqName) && strcasecmp(trim($mName), trim($reqName)) === 0) {
                    if (empty($reqTeam) || strcasecmp(trim($pivot->team_name ?? ''), trim($reqTeam)) === 0) {
                        $isMatch = true;
                    }
                }
                // Fallback: match by participantStr if name was passed directly
                elseif (! empty($participantStr) && ! str_starts_with($participantStr, 'tm_') && strcasecmp(trim($mName), trim($participantStr)) === 0) {
                    if (empty($reqTeam) || strcasecmp(trim($pivot->team_name ?? ''), trim($reqTeam)) === 0) {
                        $isMatch = true;
                    }
                }

                if ($isMatch) {
                    $member['attended_at'] = $now->toDateTimeString();
                    $member['status'] = 'Attended';
                    $rosterUpdated = true;
                    $marked = true;

                    // If member has a user_id, ensure they also have attended_at on their sports_program_user row
                    if ($mUserId && is_numeric($mUserId)) {
                        $mUser = User::find($mUserId);
                        if ($mUser) {
                            $mPivotData = [
                                'team_name' => $pivot->team_name,
                                'attended_at' => $now,
                            ];
                            if (! empty($sportsProgram->certificate_template_path) && Schema::hasColumn('sports_program_user', 'certificate_path')) {
                                $mPivotData['certificate_path'] = $sportsProgram->certificate_template_path;
                            }
                            $mUser->joinedSportsPrograms()->syncWithoutDetaching([
                                $sportsProgram->id => $mPivotData,
                            ]);
                        }
                    }
                }
            }
            unset($member);

            if ($rosterUpdated) {
                DB::table('sports_program_user')
                    ->where('id', $pivot->id)
                    ->update([
                        'teammates' => json_encode($roster),
                        'updated_at' => $now,
                    ]);
            }
        }

        if (! $marked) {
            return response()->json(['message' => 'Participant not found in this sports program.'], 404);
        }

        return response()->json(['message' => 'Attendance marked successfully.']);
    }

    public function attendanceLogs(Request $request, $id)
    {
        $model = $this->resolveActivityModel($id, $request);
        $pivotColumn = $model instanceof SportsProgram ? 'sports_program_user.attended_at' : 'event_user.attended_at';

        // Only get participants who have attended, ordered by attendance time
        $query = $model->participants()
            ->with('youthProfile')
            ->whereNotNull($pivotColumn)
            ->orderBy($pivotColumn, 'desc');

        $perPage = $request->input('per_page', 10);

        return EventParticipantResource::collection($query->paginate($perPage));
    }

    public function uploadCertificate(Request $request, $id = null)
    {
        $model = $this->resolveActivityModel($id, $request);

        $fileKey = null;
        foreach (['certificate', 'certificates', 'file', 'template', 'image'] as $key) {
            if ($request->hasFile($key)) {
                $fileKey = $key;
                break;
            }
        }

        if (! $fileKey && empty($model->certificate_template_path)) {
            return response()->json([
                'message' => 'The certificate file is required.',
                'errors' => ['certificate' => ['The certificate file is required.']],
            ], 422);
        }

        if ($fileKey) {
            $request->validate([
                $fileKey => 'required|file|mimes:pdf,png,jpg,jpeg,webp|max:10240',
            ]);
        }

        // Parse custom settings
        $settings = [];
        if ($request->has('certificate_settings') || $request->has('certificateSettings')) {
            $rawSettings = $request->input('certificate_settings') ?? $request->input('certificateSettings');
            $settings = is_string($rawSettings) ? (json_decode($rawSettings, true) ?: []) : (array) $rawSettings;
        }

        if ($request->filled('name_x')) {
            $settings['name_x'] = (float) $request->input('name_x');
        }
        if ($request->filled('name_y')) {
            $settings['name_y'] = (float) $request->input('name_y');
        }
        if ($request->filled('font_size')) {
            $settings['font_size'] = (int) $request->input('font_size');
        }
        if ($request->filled('font_color')) {
            $settings['font_color'] = $request->input('font_color');
        }
        if ($request->filled('text_align')) {
            $settings['text_align'] = $request->input('text_align');
        }

        if ($fileKey) {
            $path = $this->certificateService->uploadTemplate($model, $request->file($fileKey), $settings);

            // Also sync certificate_path in pivot table if column exists
            try {
                $pivotTable = $model instanceof SportsProgram ? 'sports_program_user' : 'event_user';
                $foreignKey = $model instanceof SportsProgram ? 'sports_program_id' : 'event_id';
                if (Schema::hasColumn($pivotTable, 'certificate_path')) {
                    DB::table($pivotTable)->where($foreignKey, $model->id)->update(['certificate_path' => $path]);
                }
            } catch (\Throwable $e) {
                // Ignore if pivot update fails
            }
        } else {
            $this->certificateService->updateSettings($model, $settings);
            $path = $model->certificate_template_path;
        }

        $model->refresh();

        return response()->json([
            'message' => 'Certificate template uploaded successfully.',
            'certificate_template_path' => $path,
            'certificate_path' => $path,
            'certificate_settings' => $model->certificate_settings,
            'has_certificate' => true,
        ]);
    }

    public function certificatePreview(Request $request, $id = null)
    {
        $model = $this->resolveActivityModel($id, $request);

        if (empty($model->certificate_template_path)) {
            return response()->json(['message' => 'No certificate template has been uploaded for this activity.'], 404);
        }

        $customSettings = [];
        if ($request->has('certificate_settings') || $request->has('certificateSettings')) {
            $rawSettings = $request->input('certificate_settings') ?? $request->input('certificateSettings');
            $customSettings = is_string($rawSettings) ? (json_decode($rawSettings, true) ?: []) : (array) $rawSettings;
        }

        if ($request->filled('name_x')) {
            $customSettings['name_x'] = (float) $request->input('name_x');
        }
        if ($request->filled('name_y')) {
            $customSettings['name_y'] = (float) $request->input('name_y');
        }
        if ($request->filled('font_size')) {
            $customSettings['font_size'] = (int) $request->input('font_size');
        }
        if ($request->filled('font_color')) {
            $customSettings['font_color'] = $request->input('font_color');
        }
        if ($request->filled('text_align')) {
            $customSettings['text_align'] = $request->input('text_align');
        }

        $sampleName = $request->input('sample_name', 'JUAN DELA CRUZ');

        $preview = $this->certificateService->generatePreview($model, $customSettings, $sampleName);

        return response($preview['content'], 200, [
            'Content-Type' => $preview['mime'],
            'Content-Disposition' => 'inline; filename="'.$preview['filename'].'"',
        ]);
    }

    public function downloadCertificate(Request $request, $id = null)
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user() ?? $request->user();
        $model = $this->resolveActivityModel($id, $request);

        // Verify participant
        $participant = $model->participants()->where('user_id', $user->id)->first();
        if (! $participant && $user->role !== UserRole::Admin && $user->role !== UserRole::SkAdmin) {
            return response()->json(['message' => 'You are not a participant of this activity.'], 403);
        }

        // Verify attendance if regular participant
        if ($participant && (! $participant->pivot || empty($participant->pivot->attended_at))) {
            return response()->json(['message' => 'Certificate is only available for participants who attended this activity.'], 403);
        }

        // Verify activity is Completed (only Completed activities allow certificate downloads)
        $isCompleted = strtolower((string) $model->status) === 'completed';
        if (! $isCompleted && $user->role !== UserRole::Admin && $user->role !== UserRole::SkAdmin) {
            return response()->json(['message' => 'Certificate is only available once the activity is marked as Completed.'], 403);
        }

        // Verify certificate template existence
        if (empty($model->certificate_template_path)) {
            return response()->json(['message' => 'No certificate template has been uploaded for this activity.'], 404);
        }

        $certificate = $this->certificateService->generateCertificate($model, $user);

        // If JSON or base64 format requested, return base64 payload to prevent IDM XHR interception
        if ($request->input('format') === 'base64' || $request->input('format') === 'json' || $request->wantsJson()) {
            return response()->json([
                'filename' => $certificate['filename'],
                'mime' => $certificate['mime'],
                'data' => base64_encode($certificate['content']),
            ]);
        }

        return response($certificate['content'], 200, [
            'Content-Type' => $certificate['mime'],
            'Content-Disposition' => 'attachment; filename="'.$certificate['filename'].'"',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ]);
    }

    /**
     * Email personalized certificates to all attended participants of an activity.
     */
    public function sendCertificates(Request $request, $id = null)
    {
        $model = $this->resolveActivityModel($id, $request);

        if (empty($model->certificate_template_path)) {
            return response()->json(['message' => 'No certificate template has been uploaded for this activity.'], 422);
        }

        $isCompleted = strtolower((string) $model->status) === 'completed';
        if (! $isCompleted) {
            return response()->json(['message' => 'Certificates can only be emailed once the activity is marked as Completed.'], 422);
        }

        $result = $this->certificateService->sendCertificatesToAttendedParticipants($model);

        return response()->json([
            'message' => "Certificates successfully emailed to {$result['sent']} participant(s).",
            'result' => $result,
        ]);
    }

    /**
     * Email personalized certificate to a single attended participant.
     */
    public function sendParticipantCertificate(Request $request, $id, User $user)
    {
        $model = $this->resolveActivityModel($id, $request);

        if (empty($model->certificate_template_path)) {
            return response()->json(['message' => 'No certificate template has been uploaded for this activity.'], 422);
        }

        $isCompleted = strtolower((string) $model->status) === 'completed';
        if (! $isCompleted) {
            return response()->json(['message' => 'Certificates can only be emailed once the activity is marked as Completed.'], 422);
        }

        $participant = $model->participants()->where('user_id', $user->id)->first();
        if (! $participant || ! $participant->pivot || empty($participant->pivot->attended_at)) {
            return response()->json(['message' => 'Participant must be marked as attended to receive a certificate.'], 422);
        }

        $this->certificateService->sendCertificateToUser($model, $user);

        return response()->json([
            'message' => "Certificate successfully emailed to {$user->name}.",
        ]);
    }

    /**
     * Resolve Event or SportsProgram model from ID or request route parameters.
     */
    protected function resolveActivityModel($id, ?Request $request = null): Event|SportsProgram
    {
        $target = $id;

        if (! $target && $request) {
            $target = $request->route('sportsProgram')
                ?? $request->route('sport')
                ?? $request->route('event')
                ?? $request->route('id');
        }

        if ($target instanceof SportsProgram || $target instanceof Event) {
            return $target;
        }

        if (is_string($target) && Str::startsWith($target, 'sport_')) {
            $sportId = str_replace('sport_', '', $target);

            return SportsProgram::findOrFail($sportId);
        }

        if (is_string($target) && Str::startsWith($target, 'event_')) {
            $eventId = str_replace('event_', '', $target);

            return Event::findOrFail($eventId);
        }

        if ($request && ($request->route('sportsProgram') || $request->is('*sports*'))) {
            return SportsProgram::findOrFail($target);
        }

        $event = Event::find($target);
        if ($event) {
            return $event;
        }

        return SportsProgram::findOrFail($target);
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



