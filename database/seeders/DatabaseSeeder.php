<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin
        User::factory()->admin()->active()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        // 2. SK Admin
        User::factory()->skAdmin()->active()->create([
            'name' => 'SK Admin User',
            'email' => 'sk@test.com',
        ]);

        // 3. Youth
        $youthUser = User::factory()->youth()->active()->create([
            'name' => 'Youth User',
            'email' => 'youth@test.com',
        ]);

        YouthProfile::factory()->create([
            'user_id' => $youthUser->id,
            'first_name' => 'Youth',
            'last_name' => 'User',
        ]);
    }
}
