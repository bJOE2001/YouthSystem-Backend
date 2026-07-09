<?php

namespace App\Actions\SkAdmin\SkOfficials;

use App\Models\SkOfficial;
use Illuminate\Database\Eloquent\Collection;

class GetSkOfficialsAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, SkOfficial>
     */
    public function execute(array $filters = []): Collection
    {
        $query = SkOfficial::query();

        return $query->orderBy('name', 'asc')->get();
    }
}
