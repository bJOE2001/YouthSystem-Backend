<?php

namespace App\Actions\SkAdmin\SkOfficials;

use App\Models\SkOfficial;

class CreateSkOfficialAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): SkOfficial
    {
        return SkOfficial::create([
            'name' => $data['name'],
            'initials' => $data['initials'] ?? null,
            'barangay' => $data['barangay'] ?? null,
            'contact' => $data['contact'] ?? null,
            'email' => $data['email'] ?? null,
            'committee' => $data['committee'] ?? null,
            'position' => $data['position'] ?? null,
            'responsibilities' => $data['responsibilities'] ?? null,
            'term' => '2023 - 2025',
        ]);
    }
}
