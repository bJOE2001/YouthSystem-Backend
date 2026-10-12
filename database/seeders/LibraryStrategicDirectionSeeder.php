<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\StrategicDirection;

class LibraryStrategicDirectionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Sports Governance (Planning, Monitoring, Evaluation, Auditing, Reporting)',
            'Sports Promotion and Awareness (Campaigns, Orientations, Forums, Exhibits, Publicity)',
            'Sports Accessibility (Clinics, Tryouts, Tournaments, Outreach, Training)',
            'High-Performance Sports Development (Coaching, Conditioning, Mentoring, Scouting, Competing)',
            'Sports Infrastructure (Construction, Renovation, Maintenance, Procurement, Upgrading)',
            'Sports Linkages (Networking, Partnerships, Consultations, Collaboration, Benchmarking)'
        ];

        foreach ($items as $item) {
            StrategicDirection::firstOrCreate(['name' => $item]);
        }
    }
}
