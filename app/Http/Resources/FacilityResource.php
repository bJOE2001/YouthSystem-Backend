<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'location' => $this->location,
            'availableTime' => $this->available_time,
            'status' => $this->status,
            'image' => $this->image ? url('storage/'.$this->image) : null,
            'imageName' => $this->image ? url('storage/'.$this->image) : null,
            'alreadyBooked' => (bool) $this->already_booked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
