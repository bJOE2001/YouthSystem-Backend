<?php

namespace Database\Seeders;

use App\Models\YouthProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class YouthProfileSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        YouthProfile::factory()->count(25)->create();
    }
}
