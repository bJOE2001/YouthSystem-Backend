<?php

namespace Database\Seeders;

use App\Models\Committee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LibraryCommitteesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $committees = [
            'Committee on Health',
            'Committee on Education',
            'Committee on Environment',
            'Committee on Social Inclusion and Equity',
            'Committee on Global Mobility',
            'Committee on Peacebuilding and Security',
            'Committee on Agriculture',
            'Committee on Governance',
            'Committee on Economic Empowerment',
            'Committee on Active Citizenship',
        ];

        foreach ($committees as $name) {
            Committee::updateOrCreate(
                ['name' => $name],
                [
                    'description' => null,
                    'code' => Str::slug($name, '_'),
                    'status' => 'active',
                ]
            );
        }
    }
}
