<?php

namespace App\Actions\Dashboard;

use App\Enums\YouthProfileStatus;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\YouthProfile;
use Illuminate\Support\Facades\DB;

class GetAdminDashboard
{
    public function handle(): array
    {
        $approved = YouthProfile::query()
            ->where('status', YouthProfileStatus::Approved->value);

        return [

            'cards' => [

                'totalYouth' => (clone $approved)->count(),

                'lgbtq' => (clone $approved)
                    ->where('lgbtq_member', true)
                    ->count(),

                'single' => (clone $approved)
                    ->where('civil_status', 'Single')
                    ->count(),

                'liveIn' => (clone $approved)
                    ->where('civil_status', 'Live In')
                    ->count(),

                'married' => (clone $approved)
                    ->where('civil_status', 'Married')
                    ->count(),

                'soloParent' => (clone $approved)
                    ->where('solo_parent', true)
                    ->count(),

                'pwd' => (clone $approved)
                    ->where('has_disability', true)
                    ->count(),

                'ip' => (clone $approved)
                    ->whereNotNull('special_youth_sector')
                    ->where('special_youth_sector', 'IP')
                    ->count(),

                'nonIp' => (clone $approved)
                    ->where(function ($query) {
                        $query->whereNull('special_youth_sector')
                            ->orWhere('special_youth_sector', '<>', 'IP');
                    })
                    ->count(),

            ],

            'charts' => [

                'genderDistribution' => (clone $approved)
                    ->select('gender', DB::raw('COUNT(*) as total'))
                    ->groupBy('gender')
                    ->get(),

                'youthPerBarangay' => (function () use ($approved) {
                    $allBarangays = \App\Models\Barangay::orderBy('name')->pluck('name');

                    $youthCounts = (clone $approved)
                        ->whereNotNull('barangay')
                        ->where('barangay', '!=', '')
                        ->select('barangay', DB::raw('COUNT(*) as total'))
                        ->groupBy('barangay')
                        ->pluck('total', 'barangay');

                    $countsLookup = [];
                    foreach ($youthCounts as $bName => $count) {
                        $cleanName = trim((string) $bName);
                        if ($cleanName !== '') {
                            $countsLookup[strtolower($cleanName)] = [
                                'name' => $cleanName,
                                'total' => (int) $count,
                                'matched' => false,
                            ];
                        }
                    }

                    $barangayList = collect();

                    foreach ($allBarangays as $bName) {
                        $cleanName = trim((string) $bName);
                        if ($cleanName === '') {
                            continue;
                        }

                        $key = strtolower($cleanName);
                        $total = 0;
                        if (isset($countsLookup[$key])) {
                            $total = $countsLookup[$key]['total'];
                            $countsLookup[$key]['matched'] = true;
                        }

                        $barangayList->push([
                            'barangay' => $cleanName,
                            'total' => $total,
                        ]);
                    }

                    foreach ($countsLookup as $item) {
                        if (! $item['matched']) {
                            $barangayList->push([
                                'barangay' => $item['name'],
                                'total' => $item['total'],
                            ]);
                        }
                    }

                    return $barangayList
                        ->sortBy('barangay', SORT_NATURAL | SORT_FLAG_CASE)
                        ->values();
                })(),

            ],

            'announcements' => AnnouncementResource::collection(
                Announcement::latest()->take(5)->get()
            )->resolve(),

            'engagement_metrics' => app(GetEngagementMetricsAction::class)->execute(),

            'youth_participation' => app(GetYouthParticipationAction::class)->execute(),

        ];
    }
}
