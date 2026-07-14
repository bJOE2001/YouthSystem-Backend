<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = 'Admin User';
        $contact = '';
        $purok = '';

        if ($this->role === UserRole::Youth && $this->youthProfile) {
            $name = $this->youthProfile->first_name.' '.$this->youthProfile->last_name;
            $contact = $this->youthProfile->mobile_number;
            $purok = $this->youthProfile->purok_sitio;
        } elseif ($this->role === UserRole::SkAdmin) {
            $name = 'SK Admin User';
        }

        return [
            'id' => $this->id,
            'name' => $name,
            'contact' => $contact,
            'email' => $this->email,
            'purok' => $purok,
            // Map attended_at to 'Attended' or 'Not Attended' for the frontend
            'status' => ($this->pivot && $this->pivot->attended_at) ? 'Attended' : 'Not Attended',
        ];
    }
}
