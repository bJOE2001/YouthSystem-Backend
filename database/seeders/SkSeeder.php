<?php

namespace Database\Seeders;

use App\Enums\YouthProfileStatus;
use App\Models\Barangay;
use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Database\Seeder;

class SkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangays = [
            'Bincungan',
            'Busaon',
            'Canocotan',
            'Cuambogan',
            'La Filipina',
        ];

        foreach ($barangays as $barangayName) {
            $barangay = Barangay::firstOrCreate(['name' => $barangayName]);
            $bName = strtolower(str_replace(' ', '', $barangayName));

            // Create SK Admin
            $adminEmail = "skadmin_{$bName}@test.com";
            if (! User::where('email', $adminEmail)->exists()) {
                $skAdminUser = User::factory()->skAdmin()->active()->create([
                    'name' => "SK Admin {$barangayName}",
                    'email' => $adminEmail,
                ]);

                // Create approved youth profile for SK Admin
                YouthProfile::factory()->create([
                    'user_id' => $skAdminUser->id,
                    'first_name' => 'SK Admin',
                    'last_name' => $barangayName,
                    'barangay' => $barangay->name,
                    'status' => YouthProfileStatus::Approved,
                ]);

                SkOfficial::factory()->create([
                    'user_id' => $skAdminUser->id,
                    'name' => "SK Admin {$barangayName}",
                    'email' => $adminEmail,
                    'position' => 'SK Chairperson',
                    'barangay' => $barangay->name,
                ]);

                // Create 3 Youths for this SK Admin / Barangay
                for ($i = 1; $i <= 3; $i++) {
                    $youthEmail = "youth_{$bName}_{$i}@test.com";
                    $youthName = "Youth {$i} {$barangayName}";

                    if (! User::where('email', $youthEmail)->exists()) {
                        $youthUser = User::factory()->youth()->active()->create([
                            'name' => $youthName,
                            'email' => $youthEmail,
                        ]);

                        YouthProfile::factory()->create([
                            'user_id' => $youthUser->id,
                            'first_name' => "Youth {$i}",
                            'last_name' => $barangayName,
                            'barangay' => $barangay->name,
                        ]);
                    }
                }
            }
        }
    }
}
