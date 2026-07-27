<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LydcMemberResource extends JsonResource
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
            'initials' => $this->initials,
            'barangay' => $this->barangay,
            'contact' => $this->contact,
            'email' => $this->email,
            'committee' => $this->committee,
            'position' => $this->position,
            'organization' => $this->organization,
            'sector' => $this->sector,
            'responsibilities' => $this->responsibilities,
            'status' => $this->status ?? 'Active',
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
