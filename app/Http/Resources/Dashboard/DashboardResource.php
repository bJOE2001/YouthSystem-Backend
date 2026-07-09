<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cards = $this->resource['cards'];

        return [
            'cards' => [
                'totalYouth' => (int) $cards->total_youth,
                'lgbtq' => (int) $cards->lgbtq,
                'single' => (int) $cards->single,
                'liveIn' => (int) $cards->live_in,
                'married' => (int) $cards->married,
                'soloParent' => (int) $cards->solo_parent,
                'pwd' => (int) $cards->pwd,
                'ip' => (int) $cards->ip,
                'nonIp' => (int) $cards->non_ip,
            ],
            'charts' => [
                'genderDistribution' => $this->resource['charts']['genderDistribution'],
                'youthPerPurok' => $this->resource['charts']['youthPerPurok'],
            ],
            'latestAnnouncements' => $this->resource['latestAnnouncements'],
        ];
    }
}
