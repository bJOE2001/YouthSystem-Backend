<?php

namespace Database\Seeders;

use App\Enums\YouthProfileStatus;
use App\Models\Barangay;
use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin
        if (! User::where('email', 'admin@test.com')->exists()) {
            User::factory()->admin()->active()->create([
                'name' => 'Admin User',
                'email' => 'admin@test.com',
            ]);
        }

        // 2. SK Admin
        if (! User::where('email', 'sk@test.com')->exists()) {
            $skAdminUser = User::factory()->skAdmin()->active()->create([
                'name' => 'SK Admin User',
                'email' => 'sk@test.com',
            ]);

            $barangay = Barangay::where('name', 'Apokon')->first();
            $bName = $barangay ? $barangay->name : 'Apokon';

            YouthProfile::factory()->create([
                'user_id' => $skAdminUser->id,
                'first_name' => 'SK Admin',
                'middle_name' => 'Official',
                'last_name' => 'User',
                'suffix' => null,
                'gender' => 'Male',
                'birth_date' => '2002-05-10',
                'place_of_birth' => 'Tagum City, Davao del Norte',
                'mobile_number' => '09123456788',
                'barangay' => $bName,
                'purok_sitio' => 'Purok 1',
                'city' => 'Tagum City',
                'province' => 'Davao del Norte',
                'postal_code' => '8100',
                'educational_attainment' => 'College Graduate',
                'course_strand' => 'BS Public Administration',
                'status' => YouthProfileStatus::Approved,
            ]);

            SkOfficial::factory()->create([
                'user_id' => $skAdminUser->id,
                'name' => 'SK Admin User',
                'email' => 'sk@test.com',
                'position' => 'SK Chairperson',
                'committee' => 'Sports',
                'barangay' => $bName,
            ]);
        }

        // 3. Youth
        if (! User::where('email', 'youth@test.com')->exists()) {
            $youthUser = User::factory()->youth()->active()->create([
                'name' => 'Juan Dela Cruz',
                'email' => 'youth@test.com',
            ]);

            $barangay = Barangay::where('name', 'Apokon')->first();

            YouthProfile::factory()->create([
                'user_id' => $youthUser->id,
                'first_name' => 'Juan',
                'middle_name' => 'Ponce',
                'last_name' => 'Dela Cruz',
                'suffix' => null,
                'gender' => 'Male',
                'birth_date' => '2005-08-15',
                'place_of_birth' => 'Tagum City, Davao del Norte',
                'mobile_number' => '09123456789',
                'father_first_name' => 'Jose',
                'father_middle_name' => 'Manley',
                'father_last_name' => 'Dela Cruz',
                'mother_first_name' => 'Maria',
                'mother_middle_name' => 'Celia',
                'mother_last_name' => 'Ponce',
                'parents_contact_number' => '09987654321',
                'guardian_first_name' => 'Pedro',
                'guardian_last_name' => 'Penduko',
                'guardian_contact_number' => '09112223333',
                'currently_attending_school' => true,
                'senior_high_graduate' => true,
                'educational_attainment' => 'College Level',
                'course_strand' => 'BS Information Technology',
                'ethnicity' => 'Visayan',
                'religious_affiliation' => 'Roman Catholic',
                'has_disability' => false,
                'overseas_worker' => false,
                'lgbtq_member' => false,
                'special_youth_sector' => 'None',
                'attached_id_path' => null,
                'birth_registered' => true,
                'civil_status' => 'Single',
                'solo_parent' => false,
                'barangay' => $barangay ? $barangay->name : 'Apokon',
                'purok_sitio' => 'Purok 1',
                'city' => 'Tagum City',
                'province' => 'Davao del Norte',
                'postal_code' => '8100',
            ]);
        }
    }
}
