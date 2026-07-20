<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSportsProgramRequest;
use App\Http\Requests\UpdateSportsProgramRequest;
use App\Http\Resources\EventParticipantResource;
use App\Http\Resources\SportsProgramResource;
use App\Models\SportsProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SportsProgramController extends Controller
{
    public function index(Request $request)
    {
        $query = SportsProgram::query();
        $user = auth('sanctum')->user();

        $isOwnerRequest = $request->input('owner') === 'me';

        if ($isOwnerRequest && $user && ($user->role === UserRole::Admin || $user->role === UserRole::SkAdmin)) {
            $query->where('user_id', $user->id);
        } else {
            // By default only show certain statuses if not owner? The frontend table shows all programs for the admin.
            // Let's just fetch all if not owner, or we can leave it to the user's role.
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
        $data = $this->mapToSnakeCase($request->validated());
        $data['user_id'] = auth()->id();
        $program = SportsProgram::create($data);

        return new SportsProgramResource($program);
    }

    public function show(SportsProgram $sportsProgram)
    {
        return new SportsProgramResource($sportsProgram);
    }

    public function update(UpdateSportsProgramRequest $request, SportsProgram $sportsProgram)
    {
        $data = $this->mapToSnakeCase($request->validated());
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

        // Group by barangay
        $grouped = collect($resourceCollection)->groupBy(function ($participant) {
            return ! empty($participant['barangay']) ? $participant['barangay'] : 'Unknown';
        });

        // Map to expected format
        $result = [];
        foreach ($grouped as $barangay => $items) {
            $result[] = [
                'barangay' => $barangay,
                'participants' => $items,
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

    private function mapToSnakeCase(array $data): array
    {
        $mapped = [];
        foreach ($data as $key => $value) {
            $snakeKey = Str::snake($key);

            // Fix for objective1 -> objective_1
            if (preg_match('/^objective(\d+)$/', $snakeKey, $matches)) {
                $snakeKey = 'objective_'.$matches[1];
            }

            $mapped[$snakeKey] = $value;
        }

        return $mapped;
    }
}
