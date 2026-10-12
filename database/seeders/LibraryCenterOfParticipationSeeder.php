<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CenterOfParticipation;

class LibraryCenterOfParticipationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $centersOfParticipation = [
            'Health',
            'Education',
            'Economic Empowerment',
            'Social Inclusion and Equity',
            'Peace Building and Security',
            'Governance',
            'Active Citizenship',
            'Environment',
            'Global Mobility',
            'Agriculture'

        ];
            foreach ($centersOfParticipation as $center) {
                CenterOfParticipation::firstOrCreate(
                    ['name' => $center],
                    ['status' => 'active'],
                );
        }
    }
}
