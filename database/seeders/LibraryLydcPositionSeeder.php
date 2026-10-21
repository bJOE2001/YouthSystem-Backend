<?php

namespace Database\Seeders;

use App\Models\LydcPosition;
use Illuminate\Database\Seeder;

class LibraryLydcPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            'LYDC Chairperson',
            'LYDC Internal Vice-Chairperson',
            'LYDC External Vice-Chairperson',
            'LYDC Member',
        ];

        foreach ($positions as $position) {
            LydcPosition::firstOrCreate(
                ['name' => $position],
                ['status' => 'active']
            );
        }
    }
}
