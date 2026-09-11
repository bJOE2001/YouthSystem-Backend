<?php

namespace Database\Seeders;

use App\Models\ScholarPosition;
use Illuminate\Database\Seeder;

class LibraryScholarPositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'Cluster President',
                'can_scan' => true,
            ],
        ];

        foreach ($positions as $position) {
            ScholarPosition::firstOrCreate(
                ['name' => $position['name']],
                [
                    'can_scan' => $position['can_scan'],
                    'status' => 'active',
                ]
            );
        }
    }
}
