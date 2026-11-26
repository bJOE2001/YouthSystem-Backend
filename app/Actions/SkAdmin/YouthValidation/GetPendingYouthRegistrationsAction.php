<?php

namespace App\Actions\SkAdmin\YouthValidation;

use App\Enums\UserRole;
use App\Enums\YouthProfileStatus;
use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthValidation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GetPendingYouthRegistrationsAction
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = YouthValidation::query()
            ->where('status', YouthProfileStatus::Pending->value);

        $user = Auth::user();
        if ($user instanceof User && $user->role === UserRole::SkAdmin) {
            $skOfficial = SkOfficial::where('email', $user->email)->first();
            if ($skOfficial && $skOfficial->barangay) {
                $query->where('barangay', $skOfficial->barangay);
            }
        }

        if (! empty($filters['barangay'])) {
            $query->where('barangay', $filters['barangay']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $search)
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', middle_name, ' ', last_name)"), 'LIKE', $search)
                    ->orWhere('mobile_number', 'LIKE', $search)
                    ->orWhere('email', 'LIKE', $search)
                    ->orWhere('barangay', 'LIKE', $search)
                    ->orWhere('purok_sitio', 'LIKE', $search);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $sortMap = [
            'name' => 'first_name',
            'contact' => 'mobile_number',
            'barangay' => 'barangay',
            'purok' => 'purok_sitio',
            'age' => 'birth_date',
            'created_at' => 'created_at',
        ];

        if (array_key_exists($sortBy, $sortMap)) {
            if ($sortBy === 'age') {
                $sortOrder = $sortOrder === 'asc' ? 'desc' : 'asc';
            }
            $query->orderBy($sortMap[$sortBy], $sortOrder);
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }
}
