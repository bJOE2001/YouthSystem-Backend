<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LibraryBarangaysSeeder::class,
            LibraryCommitteesSeeder::class,
            LibraryOrganizationsSeeder::class,
            LibraryScholarPositionsSeeder::class,
            LibrarySdgSeeder::class,
            LibraryCenterOfParticipationSeeder::class,
            LibraryStrategicDirectionSeeder::class,
            LibraryNoBudgetReasonSeeder::class,
            LibraryLydcPositionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
