<?php

namespace App\Actions\SkAdmin\YouthValidation;

use App\Enums\UserRole;
use App\Enums\YouthProfileStatus;
use App\Models\SkOfficial;
use App\Models\YouthProfile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GetPendingYouthRegistrationsAction
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = YouthProfile::query()
            ->with('user')
            ->where('status', YouthProfileStatus::Pending->value);

        if ($user = auth()->user()) {
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

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        // Translate frontend sort_by fields to DB columns
        $sortMap = [
            'name' => 'first_name',
            'contact' => 'mobile_number',
            'purok' => 'purok_sitio',
            'age' => 'birth_date', // Technically sorting by birth_date ascending means older first
            'created_at' => 'created_at',
        ];

        if (array_key_exists($sortBy, $sortMap)) {
            // If sorting by age, we should reverse the sort order since birth_date DESC = youngest, ASC = oldest.
            if ($sortBy === 'age') {
                $sortOrder = $sortOrder === 'asc' ? 'desc' : 'asc';
            }
            $query->orderBy($sortMap[$sortBy], $sortOrder);
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }
}
