<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class LibraryOrganizationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = [
            'SINAG',
        ];

        foreach ($organizations as $orgName) {
            Organization::firstOrCreate(
                ['name' => $orgName],
                ['status' => 'active']
            );
        }
    }
}
