<?php

namespace App\Http\Controllers\Api;

use App\Actions\SkAdmin\ResidentYouth\GetResidentYouthRecordsAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSportsProgramRequest;
use App\Http\Requests\UpdateSportsProgramRequest;
use App\Http\Resources\EventParticipantResource;
use App\Http\Resources\SkAdmin\ResidentYouthListResource;
use App\Http\Resources\SportsProgramResource;
use App\Http\Resources\UnifiedEventResource;
use App\Models\SkOfficial;
use App\Models\SportsProgram;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SportsProgramController extends Controller
{
    public function index(Request $request)
    {
        $query = SportsProgram::query();
        /** @var User|null $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user();

        $isOwnerRequest = $request->input('owner') === 'me';

        if ($isOwnerRequest && $user && ($user->role === UserRole::Admin || $user->role === UserRole::SkAdmin)) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereIn('status', ['Upcoming', 'Ongoing', 'upcoming', 'ongoing']);

            $userBarangay = null;
            if ($user) {
                $userBarangay = $user->youthProfile->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;
            }

            if (! $user || $user->role !== UserRole::Admin) {
                $query->where(function ($q) use ($userBarangay) {
                    $q->where('open_to_all_barangays', true);
                    if ($userBarangay) {
                        $q->orWhereRaw('LOWER(barangay) = ?', [strtolower(trim($userBarangay))]);
                    }
                });
            }
        }

        if ($request->has('search') && ! empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('location', 'like', '%'.$request->search.'%')
                    ->orWhere('type', 'like', '%'.$request->search.'%');
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

        return SportsProgramResource::collection($query->paginate($perPage));
    }

    public function store(StoreSportsProgramRequest $request)
    {
        /** @var User $user */
        $user = $request->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
        $data = $this->mapToSnakeCase($request->validated());
        $data['user_id'] = $user->id;

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

    public function show(SportsProgram $sportsProgram)
    {
        return new SportsProgramResource($sportsProgram);
    }

    public function update(UpdateSportsProgramRequest $request, SportsProgram $sportsProgram)
    {
        /** @var User $user */
        $user = $request->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
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

    public function updateStatus(Request $request, SportsProgram $sportsProgram)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $status = ucfirst(strtolower($request->status)); // To match 'Completed', 'Upcoming', 'Draft', etc.
        $sportsProgram->update(['status' => $status]);

        return new SportsProgramResource($sportsProgram);
    }

    public function destroy(SportsProgram $sportsProgram)
    {
        $sportsProgram->delete();

        return response()->noContent();
    }

    public function participantsByBarangay(Request $request, SportsProgram $sportsProgram)
    {
        // Get all participants
        $participants = $sportsProgram->participants()->with('youthProfile')->get();

        // Convert to resource array
        $resourceCollection = EventParticipantResource::collection($participants)->resolve($request);

        $expandedParticipants = [];
        $registeredUserIds = $participants->pluck('id')->toArray();

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

                    $expandedParticipants[] = [
                        'id' => 'tm_'.md5($p['id'].'_'.$tmName),
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
                        'status' => 'Not Attended',
                    ];
                }
            }
        }

        // Group by barangay
        $grouped = collect($expandedParticipants)->groupBy(function ($participant) {
            return ! empty($participant['barangay']) ? $participant['barangay'] : 'Unknown';
        });

        // Map to expected format
        $result = [];
        foreach ($grouped as $barangay => $items) {
            $result[] = [
                'barangay' => $barangay,
                'participants' => $items->values()->all(),
            ];
        }

        // Sort by barangay name alphabetically
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

    public function join(Request $request, SportsProgram $sportsProgram)
    {
        $validated = $request->validate([
            'team_name' => 'nullable|string|max:255',
            'leader_id' => 'nullable',
            'leader' => 'nullable|array',
            'teammates' => 'nullable|array',
            'teammates.*.user_id' => 'nullable',
            'teammates.*.name' => 'nullable|string',
            'teammates.*.email' => 'nullable|string',
            'teammates.*.contact' => 'nullable|string',
            'teammates.*.role' => 'nullable|string',
        ]);

        /** @var User|null $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user() ?? $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userRoleStr = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
        $isAdminOrSk = in_array(strtolower($userRoleStr), ['admin', 'sk_admin', 'superadmin']);

        if (! $sportsProgram->open_to_all_barangays && ! $isAdminOrSk) {
            $userBarangay = $user->youthProfile->barangay ?? SkOfficial::where('email', $user->email)->value('barangay') ?? null;
            if (! $userBarangay || strtolower(trim($userBarangay)) !== strtolower(trim($sportsProgram->barangay))) {
                return response()->json(['message' => 'This sports program is exclusive to residents of Barangay '.$sportsProgram->barangay.'.'], 403);
            }
        }

        $teamName = $validated['team_name'] ?? null;
        $rawTeammates = $validated['teammates'] ?? [];

        // Check if explicit leader was passed
        $leaderData = $validated['leader'] ?? null;
        if (! $leaderData && ! empty($rawTeammates)) {
            foreach ($rawTeammates as $tm) {
                if (($tm['role'] ?? '') === 'Team Leader' || ! empty($tm['is_captain']) || ! empty($tm['is_leader'])) {
                    $leaderData = $tm;
                    break;
                }
            }
        }

        // Helper to check if a user is already registered in this sports program (pivot or roster JSON)
        $existingPivots = DB::table('sports_program_user')
            ->where('sports_program_id', $sportsProgram->id)
            ->get();

        $checkExistingRegistration = function ($userId, $email = null, $name = null) use ($existingPivots) {
            $emailNorm = ! empty($email) && $email !== '—' && strtolower($email) !== 'no email' ? strtolower(trim($email)) : null;
            $nameNorm = ! empty($name) && $name !== '—' ? strtolower(trim(preg_replace('/[^\w\s]/', '', $name))) : null;

            foreach ($existingPivots as $pivot) {
                $teamName = $pivot->team_name ?: 'another team';
                if ($userId && $pivot->user_id == $userId) {
                    return $teamName;
                }

                if (! empty($pivot->teammates)) {
                    $roster = is_string($pivot->teammates) ? json_decode($pivot->teammates, true) : $pivot->teammates;
                    if (is_array($roster)) {
                        foreach ($roster as $member) {
                            $mUserId = $member['user_id'] ?? null;
                            $mEmail = ! empty($member['email']) && $member['email'] !== '—' && strtolower($member['email']) !== 'no email' ? strtolower(trim($member['email'])) : null;
                            $mName = ! empty($member['name']) && $member['name'] !== '—' ? strtolower(trim(preg_replace('/[^\w\s]/', '', $member['name']))) : null;

                            if ($userId && $mUserId && $userId == $mUserId) {
                                return $teamName;
                            }
                            if ($emailNorm && $mEmail && $emailNorm === $mEmail) {
                                return $teamName;
                            }
                            if ($nameNorm && $mName && ($nameNorm === $mName || str_contains($nameNorm, $mName) || str_contains($mName, $nameNorm))) {
                                return $teamName;
                            }
                        }
                    }
                }
            }

            return null;
        };

        if ($isAdminOrSk && $leaderData) {
            $captainUserId = $leaderData['user_id'] ?? $leaderData['id'] ?? $validated['leader_id'] ?? null;
            $leaderUser = $captainUserId ? User::find($captainUserId) : null;
            if (! $leaderUser && $captainUserId) {
                $leaderUser = User::whereHas('youthProfile', function ($q) use ($captainUserId) {
                    $q->where('id', $captainUserId);
                })->first();
            }
            if (! $leaderUser && ! empty($leaderData['email'])) {
                $leaderUser = User::where('email', $leaderData['email'])->first();
            }

            if (! $leaderUser) {
                return response()->json(['message' => 'Please select a valid resident youth as Team Leader.'], 422);
            }

            // Check if leader is already registered in this sports program
            $leaderName = $leaderUser->name ?? ($leaderData['name'] ?? 'The selected team leader');
            $leaderEmail = $leaderUser->email ?? ($leaderData['email'] ?? null);
            $existingTeam = $checkExistingRegistration($leaderUser->id, $leaderEmail, $leaderName);
            if ($existingTeam) {
                return response()->json([
                    'message' => $leaderName.' is already registered in team "'.$existingTeam.'" for this sports program.',
                ], 422);
            }

            $captainInfo = [
                'user_id' => $leaderUser->id,
                'name' => $leaderName,
                'email' => $leaderEmail ?? '',
                'contact' => $leaderData['contact'] ?? '',
                'role' => 'Team Leader',
            ];
        } else {
            $existingTeam = $checkExistingRegistration($user->id, $user->email, $user->name);
            if ($existingTeam) {
                return response()->json([
                    'message' => 'You are already registered in team "'.$existingTeam.'" for this sports program.',
                ], 400);
            }

            $captainInfo = [
                'user_id' => $user->id,
                'name' => $user->name ?? trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
                'email' => $user->email,
                'contact' => $user->youthProfile?->mobile_number ?? $user->contact_number ?? '',
                'role' => 'Team Leader',
            ];
            $leaderUser = $user;
        }

        $formattedTeammates = [];
        foreach ($rawTeammates as $t) {
            $tRole = $t['role'] ?? 'Member';
            $memberUserId = $t['user_id'] ?? $t['id'] ?? null;

            $memberUser = null;
            if ($memberUserId) {
                $memberUser = User::find($memberUserId);
                if (! $memberUser) {
                    $memberUser = User::whereHas('youthProfile', function ($q) use ($memberUserId) {
                        $q->where('id', $memberUserId);
                    })->first();
                }
            }
            if (! $memberUser && ! empty($t['email'])) {
                $memberUser = User::where('email', $t['email'])->first();
            }

            $realMemberUserId = $memberUser ? $memberUser->id : $memberUserId;
            $memberName = $memberUser?->name ?? ($t['name'] ?? '');
            $memberEmail = $memberUser?->email ?? ($t['email'] ?? '');

            if ($tRole === 'Team Leader' || ($captainInfo['user_id'] && $realMemberUserId == $captainInfo['user_id']) || (! empty($t['name']) && $t['name'] === $captainInfo['name'])) {
                continue;
            }

            // Check if member is already registered in this sports program
            $existingTeam = $checkExistingRegistration($realMemberUserId, $memberEmail, $memberName);
            if ($existingTeam) {
                return response()->json([
                    'message' => ($memberName ?: 'A member').' is already registered in team "'.$existingTeam.'" for this sports program.',
                ], 422);
            }

            $formattedTeammates[] = [
                'user_id' => $realMemberUserId,
                'name' => $memberName,
                'email' => $memberEmail,
                'contact' => $t['contact'] ?? '',
                'role' => $tRole,
            ];
        }

        $allRoster = array_merge([$captainInfo], $formattedTeammates);
        $encodedRoster = json_encode($allRoster);

        // 1. Attach Team Leader
        $leaderUser->joinedSportsPrograms()->syncWithoutDetaching([
            $sportsProgram->id => [
                'team_name' => $teamName,
                'teammates' => $encodedRoster,
            ],
        ]);

        // 2. Automatically register / attach all added teammates who have an account
        foreach ($formattedTeammates as $member) {
            $memberUserId = $member['user_id'] ?? null;
            if ($memberUserId && $memberUserId != $leaderUser->id) {
                $memberUser = User::find($memberUserId);
                if ($memberUser) {
                    $memberUser->joinedSportsPrograms()->syncWithoutDetaching([
                        $sportsProgram->id => [
                            'team_name' => $teamName,
                            'teammates' => $encodedRoster,
                        ],
                    ]);
                }
            }
        }

        return new UnifiedEventResource($sportsProgram);
    }

    /**
     * Search resident youth for sports team roster registration (delegates to GetResidentYouthRecordsAction).
     */
    public function searchResidents(Request $request, GetResidentYouthRecordsAction $action)
    {
        /** @var User|null $user */
        $user = Auth::guard('sanctum')->user() ?? Auth::user() ?? $request->user();
        if (! $user) {
            return response()->json(['data' => []]);
        }

        $records = $action->execute($request->all());

        return ResidentYouthListResource::collection($records)->response();
    }

    private function mapToSnakeCase(array $data): array
    {
        $mapped = [];
        foreach ($data as $key => $value) {
            $snakeKey = Str::snake($key);

            if ($key === 'openToAll' || $key === 'open_to_all') {
                $snakeKey = 'open_to_all_barangays';
            }

            // Fix for objective1 -> objective_1
            if (preg_match('/^objective(\d+)$/', $snakeKey, $matches)) {
                $snakeKey = 'objective_'.$matches[1];
            }

            $mapped[$snakeKey] = $value;
        }

        return $mapped;
    }
}
