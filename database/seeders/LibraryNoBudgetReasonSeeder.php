<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\NoBudgetReason;

class LibraryNoBudgetReasonSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Activity funded by partners',
            'Volunteer-led activity',
            'In-kind support only',
            'Internally resourced by LGU',
            'Activity funded by national agency'
        ];

        foreach ($items as $item) {
            NoBudgetReason::firstOrCreate(['name' => $item]);
        }
    }
}
