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

                'youthPerBarangay' => (clone $approved)
                    ->select('barangay', DB::raw('COUNT(*) as total'))
                    ->groupBy('barangay')
                    ->orderBy('barangay')
                    ->get(),

            ],

            'announcements' => AnnouncementResource::collection(
                Announcement::latest()->take(5)->get()
            )->resolve(),

        ];
    }
}
