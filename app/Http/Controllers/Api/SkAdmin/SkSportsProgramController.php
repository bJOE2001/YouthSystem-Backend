<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSportsProgramRequest;
use App\Http\Requests\UpdateSportsProgramRequest;
use App\Http\Resources\EventParticipantResource;
use App\Http\Resources\SportsProgramResource;
use App\Models\SkOfficial;
use App\Models\SportsProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SkSportsProgramController extends Controller
{
    /**
     * Get list of sports programs for SK Admin.
     */
    public function index(Request $request)
    {
        $query = SportsProgram::query();
        $user = auth('sanctum')->user();

        // Filter by user's programs if requested or by default for SK management
        if ($request->input('owner') === 'me' || ! $request->has('all')) {
            $query->where('user_id', $user->id);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('location', 'like', '%'.$request->search.'%')
                    ->orWhere('type', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('status') && ! empty($request->status)) {
            $query->where('status', ucfirst(strtolower($request->status)));
        }

        if ($request->has('sort_by') && ! empty($request->sort_by)) {
            $sortBy = Str::snake($request->sort_by);
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $perPage = $request->input('per_page', 10);

        return SportsProgramResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created sports program.
     */
    public function store(StoreSportsProgramRequest $request)
    {
        $user = auth()->user();
        $data = $this->mapToSnakeCase($request->validated());
        $data['user_id'] = $user->id;
        if (empty($data['status'])) {
            $data['status'] = 'Draft';
        }

        $isOpenToAll = filter_var($request->input('openToAll', $request->input('open_to_all_barangays', true)), FILTER_VALIDATE_BOOLEAN);
        $data['open_to_all_barangays'] = $isOpenToAll;

        if (! $isOpenToAll) {
            $data['barangay'] = $user->youthProfile->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;
        } else {
            $data['barangay'] = null;
        }

        $program = SportsProgram::create($data);

        return new SportsProgramResource($program);
    }

    /**
     * Display the specified sports program.
     */
    public function show(SportsProgram $sportsProgram)
    {
        return new SportsProgramResource($sportsProgram);
    }

    /**
     * Update the specified sports program.
     */
    public function update(UpdateSportsProgramRequest $request, SportsProgram $sportsProgram)
    {
        $user = auth()->user();
        $data = $this->mapToSnakeCase($request->validated());

        if ($request->has('openToAll') || $request->has('open_to_all_barangays')) {
            $isOpenToAll = filter_var($request->input('openToAll', $request->input('open_to_all_barangays', true)), FILTER_VALIDATE_BOOLEAN);
            $data['open_to_all_barangays'] = $isOpenToAll;

            if (! $isOpenToAll) {
                $data['barangay'] = $user->youthProfile->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;
            } else {
                $data['barangay'] = null;
            }
        }

        $sportsProgram->update($data);

        return new SportsProgramResource($sportsProgram);
    }

    /**
     * Update status of a sports program.
     */
    public function updateStatus(Request $request, SportsProgram $sportsProgram)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $status = ucfirst(strtolower($request->status));
        $sportsProgram->update(['status' => $status]);

        return new SportsProgramResource($sportsProgram);
    }

    /**
     * Remove the specified sports program.
     */
    public function destroy(SportsProgram $sportsProgram)
    {
        $sportsProgram->delete();

        return response()->noContent();
    }

    /**
     * Get sports program participants grouped by barangay.
     */
    public function participantsByBarangay(Request $request, SportsProgram $sportsProgram)
    {
        $participants = $sportsProgram->participants()->with('youthProfile')->get();
        $resourceCollection = EventParticipantResource::collection($participants)->resolve($request);

        $expandedParticipants = [];
        $registeredUserIds = $participants->pluck('id')->toArray();
        $seenTeammateKeys = [];

        foreach ($resourceCollection as $p) {
            $expandedParticipants[] = $p;

            // If this participant has offline teammates in roster who don't have separate user accounts in the list
            $teammates = $p['teammates'] ?? [];
            if (! empty($teammates) && is_array($teammates)) {
                foreach ($teammates as $tm) {
                    $tmUserId = $tm['user_id'] ?? null;
                    $tmName = $tm['name'] ?? '';
                    $tmRole = $tm['role'] ?? 'Member';

                    // Skip the leader itself (already in list)
                    if ($tmRole === 'Team Leader' || ($tmUserId && $tmUserId == $p['id']) || $tmName === $p['name']) {
                        continue;
                    }

                    // If teammate is already registered as an independent user in this sports program, skip duplicate
                    if ($tmUserId && in_array($tmUserId, $registeredUserIds)) {
                        continue;
                    }

                    // Prevent duplicate expansion across multiple team members sharing the same roster
                    $tmKey = $tmUserId ? "uid_{$tmUserId}" : 'name_'.strtolower(trim($tmName)).'_'.strtolower(trim($p['team_name'] ?? ''));
                    if (isset($seenTeammateKeys[$tmKey])) {
                        continue;
                    }
                    $seenTeammateKeys[$tmKey] = true;

                    $tmStatus = (! empty($tm['attended_at']) || ($tm['status'] ?? '') === 'Attended') ? 'Attended' : 'Not Attended';
                    $tmId = ! empty($tm['id']) ? $tm['id'] : ($tmUserId ?: 'tm_'.md5($p['id'].'_'.$tmName));

                    $expandedParticipants[] = [
                        'id' => $tmId,
                        'user_id' => $tmUserId,
                        'name' => $tmName,
                        'profile_picture' => null,
                        'contact' => $tm['contact'] ?? '—',
                        'email' => $tm['email'] ?? '—',
                        'purok' => $p['purok'] ?? '—',
                        'barangay' => $p['barangay'] ?? 'Unknown',
                        'team_name' => $p['team_name'],
                        'position' => $tmRole ?: 'Member',
                        'teammates' => [],
                        'status' => $tmStatus,
                    ];
                }
            }
        }

        $grouped = collect($expandedParticipants)->groupBy(function ($participant) {
            return ! empty($participant['barangay']) ? $participant['barangay'] : 'Unknown';
        });

        $result = [];
        foreach ($grouped as $barangay => $items) {
            $result[] = [
                'barangay' => $barangay,
                'participants' => $items->values()->all(),
            ];
        }

        usort($result, function ($a, $b) {
            if ($a['barangay'] === 'Unknown') {
                return 1;
            }
            if ($b['barangay'] === 'Unknown') {
                return -1;
            }

            return strcmp($a['barangay'], $b['barangay']);
        });

        return response()->json(['data' => $result]);
    }

    private function mapToSnakeCase(array $data): array
    {
        $mapped = [];
        foreach ($data as $key => $value) {
            $snakeKey = Str::snake($key);

            if ($key === 'openToAll' || $key === 'open_to_all') {
                $snakeKey = 'open_to_all_barangays';
            }

            if (preg_match('/^objective(\d+)$/', $snakeKey, $matches)) {
                $snakeKey = 'objective_'.$matches[1];
            }

            $mapped[$snakeKey] = $value;
        }

        return $mapped;
    }
}
