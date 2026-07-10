<?php

namespace App\Actions\SkAdmin\Dashboard;

use App\Enums\YouthProfileStatus;
use App\Models\YouthProfile;
use Illuminate\Support\Facades\DB;

class GetDashboardAction
{
    public function handle(): array
    {
        $user = auth()->user();
        $barangay = \App\Models\SkOfficial::where('email', $user->email)->value('barangay');

        $query = YouthProfile::query()
            ->where('status', YouthProfileStatus::Approved->value);

        if ($barangay) {
            $query->where('barangay', $barangay);
        }

        // 1. Cards (Aggregations in one query for optimization)
        $cards = (clone $query)
            ->selectRaw("
                COUNT(id) as total_youth,
                SUM(CASE WHEN lgbtq_member = 1 THEN 1 ELSE 0 END) as lgbtq,
                SUM(CASE WHEN civil_status = 'Single' THEN 1 ELSE 0 END) as single,
                SUM(CASE WHEN civil_status = 'Live In' THEN 1 ELSE 0 END) as live_in,
                SUM(CASE WHEN civil_status = 'Married' THEN 1 ELSE 0 END) as married,
                SUM(CASE WHEN solo_parent = 1 THEN 1 ELSE 0 END) as solo_parent,
                SUM(CASE WHEN has_disability = 1 THEN 1 ELSE 0 END) as pwd,
                SUM(CASE WHEN special_youth_sector = 'IP' THEN 1 ELSE 0 END) as ip,
                SUM(CASE WHEN special_youth_sector IS NULL OR special_youth_sector != 'IP' THEN 1 ELSE 0 END) as non_ip
            ")
            ->first();

        // 2. Charts
        $approvedQuery = $query;

        $genderDistribution = (clone $approvedQuery)
            ->select('gender', DB::raw('COUNT(*) as total'))
            ->groupBy('gender')
            ->get();

        $youthPerPurok = (clone $approvedQuery)
            ->select('purok_sitio as purok', DB::raw('COUNT(*) as total'))
            ->groupBy('purok_sitio')
            ->orderBy('purok_sitio')
            ->get();

        // 3. Announcements (Empty for now)
        $latestAnnouncements = [];

        return [
            'cards' => $cards,
            'charts' => [
                'genderDistribution' => $genderDistribution,
                'youthPerPurok' => $youthPerPurok,
            ],
            'latestAnnouncements' => $latestAnnouncements,
        ];
    }
}
