<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'tcydo@tagumcity.gov.ph')->first();
        if (! $admin) {
            User::factory()->admin()->active()->create([
                'name' => 'TAGUM CITY YOUTH AND SPORTS DEVELOPMENT OFFICE',
                'email' => 'tcydo@tagumcity.gov.ph',
                'qr_code_token' => (string) Str::uuid(),
            ]);
        } elseif (empty($admin->qr_code_token)) {
            $admin->update(['qr_code_token' => (string) Str::uuid()]);
        }
    }
}
