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
use App\Models\AttendanceLog;
use App\Models\SkOfficial;
use App\Models\SportsProgram;
use App\Models\User;
use App\Notifications\ParticipantCancelledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class SportsProgramController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user() && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token && $token->tokenable) {
                auth()->setUser($token->tokenable);
                $request->setUserResolver(fn() => $token->tokenable);
            }
        }

        $query = SportsProgram::query();
        /** @var User|null $user */
        $user = $request->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
        $isOwnerRequest = $request->query('owner') === 'me';

        if ($isOwnerRequest) {
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            if ($user->isRootAdmin() || ($user->isSubAdmin() && $user->canAccessModule('sports_programs'))) {
                // Citywide Admin-managed sports programs created by any Admin or Sub-Admin
                $query->whereHas('user', function ($uq) {
                    $uq->whereIn('role', [UserRole::Admin->value, UserRole::SubAdmin->value, 'admin', 'sub_admin']);
                });
            } elseif ($user->isSubAdmin()) {
                abort(403, 'You do not have permission to access the sports programs module.');
            } elseif ($user->role === UserRole::SkAdmin) {
                $skBarangay = $user->getBarangay();
                if (! $skBarangay) {
                    abort(403, 'No barangay assigned to this SK Admin account.');
                }

                // Shared sports programs belonging to this SK Admin's barangay only
                $query->where(function ($q) use ($skBarangay) {
                    $q->where(function ($bq) use ($skBarangay) {
                        $bq->whereNotNull('barangay')
                            ->whereRaw('LOWER(barangay) = ?', [strtolower(trim($skBarangay))]);
                    })->orWhereHas('user', function ($uq) use ($skBarangay) {
                        $uq->whereIn('role', [UserRole::SkAdmin->value, 'sk_admin'])
                            ->whereHas('skOfficial', function ($oq) use ($skBarangay) {
                                $oq->whereRaw('LOWER(barangay) = ?', [strtolower(trim($skBarangay))]);
                            });
                    });
                });
            } else {
                abort(403, 'Unauthorized to view management sports programs.');
            }
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
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('location', 'like', '%' . $request->search . '%')
                    ->orWhere('type', 'like', '%' . $request->search . '%');
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

    protected function authorizeSportsProgramModification(User $user, SportsProgram $sportsProgram): void
    {
        if ($user->isRootAdmin()) {
            return;
        }

        if ($user->isSubAdmin()) {
            if (! $user->canAccessModule('sports_programs')) {
                abort(403, 'You do not have permission to access the sports programs module.');
            }
            $creatorRole = $sportsProgram->user?->role;
            if ($creatorRole === UserRole::SkAdmin || (! empty($sportsProgram->barangay) && ! $sportsProgram->open_to_all_barangays)) {
                abort(403, 'Sub-Admins cannot modify barangay-managed SK sports programs.');
            }
            return;
        }

        if ($user->role === UserRole::SkAdmin) {
            $skBarangay = $user->getBarangay();
            $programBarangay = $sportsProgram->barangay ?: $sportsProgram->user?->getBarangay();
            if (! $skBarangay || strtolower(trim((string) $programBarangay)) !== strtolower(trim((string) $skBarangay))) {
                abort(403, 'You are not authorized to modify sports programs outside your assigned barangay.');
            }
            return;
        }

        abort(403, 'Unauthorized.');
    }

    public function store(StoreSportsProgramRequest $request)
    {
        /** @var User $user */
        $user = $request->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
        $data = $this->mapToSnakeCase($request->validated());
        $data['user_id'] = $user->id;

        $isOpenToAll = filter_var($request->input('openToAll', $request->input('open_to_all_barangays', true)), FILTER_VALIDATE_BOOLEAN);
        $data['open_to_all_barangays'] = $isOpenToAll;
        if ($user->role === UserRole::SkAdmin) {
            $data['barangay'] = $user->getBarangay();
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
        $this->authorizeSportsProgramModification($user, $sportsProgram);

        $data = $this->mapToSnakeCase($request->validated());

        if ($request->has('openToAll') || $request->has('open_to_all_barangays')) {
            $isOpenToAll = filter_var($request->input('openToAll', $request->input('open_to_all_barangays', true)), FILTER_VALIDATE_BOOLEAN);
            $data['open_to_all_barangays'] = $isOpenToAll;
            if ($user->role === UserRole::SkAdmin) {
                $data['barangay'] = $user->getBarangay();
            } else {
                $data['barangay'] = null;
            }
        }

        $sportsProgram->update($data);

        return new SportsProgramResource($sportsProgram);
    }

    public function updateStatus(Request $request, SportsProgram $sportsProgram)
    {
        /** @var User $user */
        $user = $request->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
        $this->authorizeSportsProgramModification($user, $sportsProgram);

        $request->validate([
            'status' => 'required|string',
        ]);

        $status = ucfirst(strtolower($request->status)); // To match 'Completed', 'Upcoming', 'Draft', etc.
        $sportsProgram->update(['status' => $status]);

        return new SportsProgramResource($sportsProgram);
    }

    public function destroy(SportsProgram $sportsProgram)
    {
        /** @var User $user */
        $user = request()->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
        $this->authorizeSportsProgramModification($user, $sportsProgram);

        $sportsProgram->delete();

        return response()->noContent();
    }

    public function removeParticipant(Request $request, SportsProgram $sportsProgram, $participant)
    {
        /** @var User $user */
        $authUser = $request->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
        if ($authUser) {
            $this->authorizeSportsProgramModification($authUser, $sportsProgram);
        }

        $targetUserId = is_numeric($participant) ? (int) $participant : (int) $request->input('user_id');
        $targetName = trim((string) ($request->input('name') ?: ''));
        $targetTeamName = trim((string) ($request->input('team_name') ?: ''));

        // If targetUserId is provided/numeric, find User
        $user = $targetUserId > 0 ? User::find($targetUserId) : null;

        // 1. If user is registered in sports_program_user pivot directly, detach them
        if ($user && $sportsProgram->participants()->where('user_id', $user->id)->exists()) {
            $sportsProgram->participants()->detach($user->id);

            try {
                AttendanceLog::where('sports_program_id', $sportsProgram->id)
                    ->where('user_id', $user->id)
                    ->delete();
            } catch (\Throwable $e) {
            }

            try {
                $user->notify(new ParticipantCancelledNotification($sportsProgram->name, 'sports'));
            } catch (\Throwable $e) {
                Log::warning('Failed to send sports cancellation notification: ' . $e->getMessage());
            }
        }

        // 2. Scan and update all teammates JSON rosters for this sports program
        $pivotQuery = DB::table('sports_program_user')
            ->where('sports_program_id', $sportsProgram->id);

        if (! empty($targetTeamName)) {
            $pivotQuery->where(function ($q) use ($targetTeamName) {
                $q->whereRaw('LOWER(TRIM(team_name)) = ?', [strtolower($targetTeamName)])
                    ->orWhere('team_name', 'like', '%' . $targetTeamName . '%');
            });
        }

        $pivots = $pivotQuery->get();

        foreach ($pivots as $pivot) {
            if (empty($pivot->teammates)) {
                continue;
            }

            $roster = is_string($pivot->teammates) ? json_decode($pivot->teammates, true) : (array) $pivot->teammates;
            if (! is_array($roster)) {
                continue;
            }

            $initialCount = count($roster);
            $newRoster = [];

            foreach ($roster as $member) {
                $mUserId = ! empty($member['user_id']) ? (int) $member['user_id'] : null;
                $mId = ! empty($member['id']) ? (string) $member['id'] : '';
                $mName = trim((string) ($member['name'] ?? ''));

                $isMatch = false;

                if ($targetUserId > 0 && $mUserId && $mUserId === $targetUserId) {
                    $isMatch = true;
                } elseif (! empty($participant) && ($participant === $mId || (string) $participant === (string) $mUserId)) {
                    $isMatch = true;
                } elseif (! empty($targetName) && strcasecmp($mName, $targetName) === 0) {
                    $isMatch = true;
                }

                if ($isMatch) {
                    // If member has user account and hasn't been notified yet
                    if ($mUserId && (! $user || $mUserId !== $user->id)) {
                        $mUser = User::find($mUserId);
                        if ($mUser) {
                            try {
                                $sportsProgram->participants()->detach($mUser->id);
                                AttendanceLog::where('sports_program_id', $sportsProgram->id)
                                    ->where('user_id', $mUser->id)
                                    ->delete();
                                $mUser->notify(new ParticipantCancelledNotification($sportsProgram->name, 'sports'));
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                    continue; // Skip member, effectively removing from roster
                }

                $newRoster[] = $member;
            }

            if (count($newRoster) !== $initialCount) {
                if (empty($newRoster)) {
                    DB::table('sports_program_user')->where('id', $pivot->id)->delete();
                } else {
                    DB::table('sports_program_user')
                        ->where('id', $pivot->id)
                        ->update([
                            'teammates' => json_encode(array_values($newRoster)),
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        return response()->json([
            'message' => 'Participant removed successfully.',
        ]);
    }

    public function removeTeam(Request $request, SportsProgram $sportsProgram, $teamId)
    {
        /** @var User $user */
        $authUser = $request->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
        if ($authUser) {
            $this->authorizeSportsProgramModification($authUser, $sportsProgram);
        }

        $rawTeamName = $request->input('team_name') ?? $teamId;
        $cleanTeamName = preg_replace('/^(team_|indiv_)/', '', (string) $rawTeamName);
        $cleanTeamName = trim($cleanTeamName);

        // Find pivot rows for this sport program matching this team
        $pivots = DB::table('sports_program_user')
            ->where('sports_program_id', $sportsProgram->id)
            ->where(function ($q) use ($cleanTeamName, $teamId, $rawTeamName) {
                $q->whereRaw('LOWER(TRIM(team_name)) = ?', [strtolower($cleanTeamName)])
                    ->orWhereRaw('LOWER(TRIM(team_name)) = ?', [strtolower($rawTeamName)])
                    ->orWhere('team_name', 'like', '%' . $cleanTeamName . '%');
                if (is_numeric($teamId)) {
                    $q->orWhere('user_id', (int) $teamId);
                }
            })
            ->get();

        $userIdsToNotify = collect();

        foreach ($pivots as $pivot) {
            if ($pivot->user_id) {
                $userIdsToNotify->push((int) $pivot->user_id);
            }
            if (! empty($pivot->teammates)) {
                $roster = is_string($pivot->teammates) ? json_decode($pivot->teammates, true) : (array) $pivot->teammates;
                if (is_array($roster)) {
                    foreach ($roster as $m) {
                        if (! empty($m['user_id'])) {
                            $userIdsToNotify->push((int) $m['user_id']);
                        }
                    }
                }
            }
        }

        $userIdsToNotify = $userIdsToNotify->unique()->filter()->values();

        // Detach all registered users
        if ($userIdsToNotify->isNotEmpty()) {
            $sportsProgram->participants()->detach($userIdsToNotify->all());

            try {
                AttendanceLog::where('sports_program_id', $sportsProgram->id)
                    ->whereIn('user_id', $userIdsToNotify->all())
                    ->delete();
            } catch (\Throwable $e) {
            }

            // Send notification to each team member with an account
            $users = User::whereIn('id', $userIdsToNotify->all())->get();
            foreach ($users as $memberUser) {
                try {
                    $memberUser->notify(new ParticipantCancelledNotification($sportsProgram->name, 'sports'));
                } catch (\Throwable $e) {
                    Log::warning('Failed to notify team member: ' . $e->getMessage());
                }
            }
        }

        // Delete all matching pivot rows
        DB::table('sports_program_user')
            ->where('sports_program_id', $sportsProgram->id)
            ->where(function ($q) use ($cleanTeamName, $teamId, $rawTeamName) {
                $q->whereRaw('LOWER(TRIM(team_name)) = ?', [strtolower($cleanTeamName)])
                    ->orWhereRaw('LOWER(TRIM(team_name)) = ?', [strtolower($rawTeamName)])
                    ->orWhere('team_name', 'like', '%' . $cleanTeamName . '%');
                if (is_numeric($teamId)) {
                    $q->orWhere('user_id', (int) $teamId);
                }
            })
            ->delete();

        return response()->json([
            'message' => 'Team and participants removed successfully and notifications sent.',
        ]);
    }

    public function participantsByBarangay(Request $request, SportsProgram $sportsProgram)
    {
        // Get all participants
        $participants = $sportsProgram->participants()->with('youthProfile')->get();

        // Convert to resource array
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
                    $tmKey = $tmUserId ? "uid_{$tmUserId}" : 'name_' . strtolower(trim($tmName)) . '_' . strtolower(trim($p['team_name'] ?? ''));
                    if (isset($seenTeammateKeys[$tmKey])) {
                        continue;
                    }
                    $seenTeammateKeys[$tmKey] = true;

                    $tmStatus = (! empty($tm['attended_at']) || ($tm['status'] ?? '') === 'Attended') ? 'Attended' : 'Not Attended';
                    $tmId = ! empty($tm['id']) ? $tm['id'] : ($tmUserId ?: 'tm_' . md5($p['id'] . '_' . $tmName));

                    $expandedParticipants[] = [
                        'id' => $tmId,
                        'user_id' => $tmUserId,
                        'profile_id' => $tm['profile_id'] ?? null,
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
                return response()->json(['message' => 'This sports program is exclusive to residents of Barangay ' . $sportsProgram->barangay . '.'], 403);
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

        $checkExistingRegistration = function ($userId, $email = null) use ($existingPivots) {
            $emailNorm = ! empty($email) && $email !== '—' && strtolower($email) !== 'no email' ? strtolower(trim($email)) : null;

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

                            if ($userId && $mUserId && $userId == $mUserId) {
                                return $teamName;
                            }
                            if ($emailNorm && $mEmail && $emailNorm === $mEmail) {
                                return $teamName;
                            }
                        }
                    }
                }
            }

            return null;
        };

        if ($isAdminOrSk && $leaderData) {
            $captainUserId = $leaderData['user_id'] ?? null;
            $captainProfileId = $leaderData['profile_id'] ?? $leaderData['id'] ?? $validated['leader_id'] ?? null;
            $leaderUser = null;
            if ($captainUserId) {
                $leaderUser = User::find($captainUserId);
            }
            if (! $leaderUser && $captainProfileId) {
                $leaderUser = User::whereHas('youthProfile', function ($q) use ($captainProfileId) {
                    $q->where('id', $captainProfileId);
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
            $existingTeam = $checkExistingRegistration($leaderUser->id, $leaderEmail);
            if ($existingTeam) {
                return response()->json([
                    'message' => $leaderName . ' is already registered in team "' . $existingTeam . '" for this sports program.',
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
            $existingTeam = $checkExistingRegistration($user->id, $user->email);
            if ($existingTeam) {
                return response()->json([
                    'message' => 'You are already registered in team "' . $existingTeam . '" for this sports program.',
                ], 400);
            }

            $captainInfo = [
                'user_id' => $user->id,
                'name' => $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'email' => $user->email,
                'contact' => $user->youthProfile?->mobile_number ?? $user->contact_number ?? '',
                'role' => 'Team Leader',
            ];
            $leaderUser = $user;
        }

        $formattedTeammates = [];

        // Pre-fetch all teammate user records to avoid N+1 queries
        $memberUserIdsToFetch = [];
        $memberProfileIdsToFetch = [];
        $memberEmailsToFetch = [];
        foreach ($rawTeammates as $t) {
            $memberUserId = $t['user_id'] ?? null;
            $memberProfileId = $t['profile_id'] ?? $t['id'] ?? null;
            if ($memberUserId) $memberUserIdsToFetch[] = $memberUserId;
            if ($memberProfileId) $memberProfileIdsToFetch[] = $memberProfileId;
            if (!empty($t['email'])) $memberEmailsToFetch[] = $t['email'];
        }

        $fetchedUsersByUserId = [];
        $fetchedUsersByProfileId = [];
        $fetchedUsersByEmail = [];
        if (!empty($memberUserIdsToFetch) || !empty($memberProfileIdsToFetch) || !empty($memberEmailsToFetch)) {
            $usersQuery = User::query();

            $usersQuery->where(function ($q) use ($memberUserIdsToFetch, $memberProfileIdsToFetch, $memberEmailsToFetch) {
                $hasCondition = false;
                if (!empty($memberUserIdsToFetch)) {
                    $q->whereIn('id', $memberUserIdsToFetch);
                    $hasCondition = true;
                }
                if (!empty($memberProfileIdsToFetch)) {
                    $method = $hasCondition ? 'orWhereHas' : 'whereHas';
                    $q->$method('youthProfile', function ($ypq) use ($memberProfileIdsToFetch) {
                        $ypq->whereIn('id', $memberProfileIdsToFetch);
                    });
                    $hasCondition = true;
                }
                if (!empty($memberEmailsToFetch)) {
                    $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                    $q->$method('email', $memberEmailsToFetch);
                }
            });

            $allFetchedUsers = $usersQuery->with('youthProfile')->get();
            foreach ($allFetchedUsers as $fu) {
                $fetchedUsersByUserId[$fu->id] = $fu;
                if ($fu->youthProfile && $fu->youthProfile->id) {
                    $fetchedUsersByProfileId[$fu->youthProfile->id] = $fu;
                }
                if (!empty($fu->email)) {
                    $fetchedUsersByEmail[strtolower(trim($fu->email))] = $fu;
                }
            }
        }

        foreach ($rawTeammates as $t) {
            $tRole = $t['role'] ?? 'Member';
            $memberUserId = $t['user_id'] ?? null;
            $memberProfileId = $t['profile_id'] ?? $t['id'] ?? null;
            $memberEmailInput = !empty($t['email']) ? strtolower(trim($t['email'])) : null;

            $memberUser = null;
            if ($memberUserId && isset($fetchedUsersByUserId[$memberUserId])) {
                $memberUser = $fetchedUsersByUserId[$memberUserId];
            } elseif ($memberProfileId && isset($fetchedUsersByProfileId[$memberProfileId])) {
                $memberUser = $fetchedUsersByProfileId[$memberProfileId];
            } elseif ($memberEmailInput && isset($fetchedUsersByEmail[$memberEmailInput])) {
                $memberUser = $fetchedUsersByEmail[$memberEmailInput];
            }

            $realMemberUserId = $memberUser ? $memberUser->id : $memberUserId;
            $memberName = $memberUser?->name ?? ($t['name'] ?? '');
            $memberEmail = $memberUser?->email ?? ($t['email'] ?? '');

            if ($tRole === 'Team Leader' || ($captainInfo['user_id'] && $realMemberUserId && $realMemberUserId == $captainInfo['user_id'])) {
                continue;
            }

            // Check if member is already registered in this sports program by user_id or email
            $existingTeam = $checkExistingRegistration($realMemberUserId, $memberEmail);
            if ($existingTeam) {
                return response()->json([
                    'message' => ($memberName ?: 'A member') . ' is already registered in team "' . $existingTeam . '" for this sports program.',
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

        // Gather all users to sync to the sports program
        $syncData = [];
        $pivotAttributes = [
            'team_name' => $teamName,
            'teammates' => $encodedRoster,
        ];

        $syncData[$leaderUser->id] = $pivotAttributes;

        foreach ($formattedTeammates as $member) {
            $memberUserId = $member['user_id'] ?? null;
            if ($memberUserId && $memberUserId != $leaderUser->id) {
                // If they are in the fetched users array, they exist
                if (isset($fetchedUsersById[$memberUserId])) {
                    $syncData[$memberUserId] = $pivotAttributes;
                }
            }
        }

        // Consolidated sync without detaching
        $sportsProgram->participants()->syncWithoutDetaching($syncData);

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
                $snakeKey = 'objective_' . $matches[1];
            }

            $mapped[$snakeKey] = $value;
        }

        return $mapped;
    }
}
