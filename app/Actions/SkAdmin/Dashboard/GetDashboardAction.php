<?php

namespace App\Actions\SkAdmin\Dashboard;

use App\Enums\YouthProfileStatus;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\SkOfficial;
use App\Models\YouthProfile;
use Illuminate\Support\Facades\DB;

class GetDashboardAction
{
    public function handle(): array
    {
        $user = auth()->user();
        $barangay = $user?->getBarangay() ?? SkOfficial::where('email', $user?->email)->value('barangay');

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

        $definedPuroks = collect();
        if ($barangay) {
            $definedPuroks = \App\Models\Purok::where('barangay', $barangay)
                ->pluck('name');
        }

        $purokYouthCounts = (clone $approvedQuery)
            ->whereNotNull('purok_sitio')
            ->where('purok_sitio', '!=', '')
            ->select('purok_sitio as purok', DB::raw('COUNT(*) as total'))
            ->groupBy('purok_sitio')
            ->pluck('total', 'purok');

        $countsLookup = [];
        foreach ($purokYouthCounts as $purokName => $count) {
            $cleanName = trim((string) $purokName);
            if ($cleanName !== '') {
                $countsLookup[strtolower($cleanName)] = [
                    'name' => $cleanName,
                    'total' => (int) $count,
                    'matched' => false,
                ];
            }
        }

        $youthPerPurokList = collect();

        foreach ($definedPuroks as $purokName) {
            $cleanName = trim((string) $purokName);
            if ($cleanName === '') {
                continue;
            }

            $key = strtolower($cleanName);
            $total = 0;
            if (isset($countsLookup[$key])) {
                $total = $countsLookup[$key]['total'];
                $countsLookup[$key]['matched'] = true;
            }

            $youthPerPurokList->push([
                'purok' => $cleanName,
                'total' => $total,
            ]);
        }

        foreach ($countsLookup as $item) {
            if (! $item['matched']) {
                $youthPerPurokList->push([
                    'purok' => $item['name'],
                    'total' => $item['total'],
                ]);
            }
        }

        $youthPerPurok = $youthPerPurokList
            ->sortBy('purok', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // 3. Announcements
        $announcements = AnnouncementResource::collection(
            Announcement::latest()->take(5)->get()
        )->resolve();

        return [
            'cards' => $cards,
            'charts' => [
                'genderDistribution' => $genderDistribution,
                'youthPerPurok' => $youthPerPurok,
            ],
            'announcements' => $announcements,
        ];
    }
}
