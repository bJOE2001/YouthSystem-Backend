<?php

namespace App\Actions\SkAdmin\SkOfficials;

use App\Models\SkOfficial;
use Illuminate\Pagination\LengthAwarePaginator;

class GetSkOfficialsAction
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = SkOfficial::query();

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', $search)
                    ->orWhere('barangay', 'LIKE', $search)
                    ->orWhere('position', 'LIKE', $search);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        $query->orderBy($sortBy, $sortOrder);

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }
}
