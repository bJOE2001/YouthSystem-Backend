<?php

namespace App\Actions\SkAdmin\ResidentYouth;

use App\Enums\UserRole;
use App\Enums\YouthProfileStatus;
use App\Models\SkOfficial;
use App\Models\SportsProgram;
use App\Models\YouthProfile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GetResidentYouthRecordsAction
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = YouthProfile::query()->with('user')->where('status', YouthProfileStatus::Approved);

        $user = auth()->user();
        $includeSelf = filter_var($filters['include_self'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $includeSelf && $user && $user->role === UserRole::Youth) {
            $query->where('user_id', '!=', $user->id);
        }

        $openToAll = filter_var($filters['open_to_all'] ?? $filters['open_to_all_barangays'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! empty($filters['sports_program_id']) || ! empty($filters['sport_id'])) {
            $spId = $filters['sports_program_id'] ?? $filters['sport_id'];
            $sportProg = SportsProgram::find($spId);
            if ($sportProg) {
                $openToAll = (bool) $sportProg->open_to_all_barangays;
                if (! $openToAll && empty($filters['barangay'])) {
                    $filters['barangay'] = $sportProg->barangay ?: $sportProg->location;
                }
            }
        }

        if (! empty($filters['barangay'])) {
            $targetBarangay = trim($filters['barangay']);
            $query->where(function ($q) use ($targetBarangay) {
                $q->where('barangay', $targetBarangay)
                    ->orWhere('barangay', 'like', "%{$targetBarangay}%");
            });
        } elseif (! $openToAll) {
            if ($user && $user->role === UserRole::SkAdmin) {
                $skOfficial = SkOfficial::where('email', $user->email)->first();
                if ($skOfficial && $skOfficial->barangay) {
                    $query->where('barangay', $skOfficial->barangay);
                }
            } elseif ($user && $user->role === UserRole::Youth && $user->youthProfile?->barangay) {
                $query->where('barangay', $user->youthProfile->barangay);
            }
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $search)
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', middle_name, ' ', last_name)"), 'LIKE', $search)
                    ->orWhere('first_name', 'LIKE', $search)
                    ->orWhere('last_name', 'LIKE', $search)
                    ->orWhere('middle_name', 'LIKE', $search)
                    ->orWhere('mobile_number', 'LIKE', $search)
                    ->orWhere('barangay', 'LIKE', $search)
                    ->orWhere('purok_sitio', 'LIKE', $search)
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'LIKE', $search)
                            ->orWhere('name', 'LIKE', $search);
                    });
            });
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        // Translate frontend sort_by fields to DB columns
        $sortMap = [
            'name' => 'first_name',
            'contact' => 'mobile_number',
            'purok' => 'purok_sitio',
            'status' => 'status',
        ];

        if (array_key_exists($sortBy, $sortMap)) {
            $query->orderBy($sortMap[$sortBy], $sortOrder);
        }

        $perPage = isset($filters['per_page']) && is_numeric($filters['per_page']) && (int) $filters['per_page'] > 0
            ? min((int) $filters['per_page'], 500)
            : 10;

        return $query->paginate($perPage);
    }
}
