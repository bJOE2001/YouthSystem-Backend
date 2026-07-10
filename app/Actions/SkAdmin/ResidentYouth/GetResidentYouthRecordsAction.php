<?php

namespace App\Actions\SkAdmin\ResidentYouth;

use App\Enums\UserRole;
use App\Models\SkOfficial;
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
        $query = YouthProfile::query()->with('user')->where('status', \App\Enums\YouthProfileStatus::Approved);

        if ($user = auth()->user()) {
            $query->where('user_id', '!=', $user->id);

            if ($user->role === UserRole::SkAdmin) {
                $skOfficial = SkOfficial::where('email', $user->email)->first();
                if ($skOfficial && $skOfficial->barangay) {
                    $query->where('barangay', $skOfficial->barangay);
                }
            }
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $search)
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', middle_name, ' ', last_name)"), 'LIKE', $search)
                    ->orWhere('mobile_number', 'LIKE', $search)
                    ->orWhere('purok_sitio', 'LIKE', $search)
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'LIKE', $search);
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

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }
}
