<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use App\Models\SkOfficial;
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
        $name = $this->name ?? 'User';
        $contact = '';
        $purok = '';
        $barangay = '';

        if ($this->youthProfile) {
            $fullName = trim($this->youthProfile->first_name.' '.$this->youthProfile->last_name);
            $name = ! empty($fullName) ? $fullName : $this->name;
            $contact = $this->youthProfile->mobile_number ?? '';
            $purok = $this->youthProfile->purok_sitio ?? '';
            $barangay = $this->youthProfile->barangay ?? '';
        }

        if (empty($barangay) && $this->role === UserRole::SkAdmin) {
            $skOfficial = SkOfficial::where('email', $this->email)->first();
            if ($skOfficial) {
                if (empty($fullName ?? '')) {
                    $name = $skOfficial->name ?: $this->name;
                }
                $barangay = $skOfficial->barangay ?? '';
            }
        }

        return [
            'id' => $this->id,
            'name' => $name,
            'profile_picture' => $this->youthProfile?->profile_picture ?? '',
            'contact' => $contact,
            'email' => $this->email,
            'purok' => $purok,
            'barangay' => $barangay,
            // Map attended_at to 'Attended' or 'Not Attended' for the frontend
            'status' => ($this->pivot && $this->pivot->attended_at) ? 'Attended' : 'Not Attended',
        ];
    }
}
