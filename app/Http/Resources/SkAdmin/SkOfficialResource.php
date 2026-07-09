<?php

namespace App\Http\Resources\SkAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkOfficialResource extends JsonResource
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
            'responsibilities' => $this->responsibilities,
            'term' => $this->term,
        ];
    }
}
