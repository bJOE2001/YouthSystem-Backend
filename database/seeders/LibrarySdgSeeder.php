<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Sdg;
use Illuminate\Database\Seeder;

class LibrarySdgSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sdgs = [
            'SDG 1: No Poverty',
            'SDG 2: Zero Hunger',
            'SDG 3: Good Health and Well-being',
            'SDG 4: Quality Education',
            'SDG 5: Gender Equality',
            'SDG 6: Clean Water and Sanitation',
            'SDG 7: Affordable and Clean Energy',
            'SDG 8: Decent Work and Economic Growth',
            'SDG 9: Industry, Innovation and Infrastructure',
            'SDG 10: Reduced Inequalities',
            'SDG 11: Sustainable Cities and Communities',
            'SDG 12: Responsible Consumption and Production',
            'SDG 13: Climate Action',
            'SDG 14: Life Below Water',
            'SDG 15: Life on Land',
            'SDG 16: Peace, Justice and Strong Institutions',
            'SDG 17: Partnerships for the Goals',
        ];
        foreach ($sdgs as $sdg) {
            Sdg::firstOrCreate(
            ['name' => $sdg],
            ['status' => 'active'],);

        }
    }
}
