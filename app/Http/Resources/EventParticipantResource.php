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
            $fullName = trim($this->youthProfile->first_name . ' ' . $this->youthProfile->last_name);
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

        $teamName = $this->pivot?->team_name ?? null;
        $teammates = $this->pivot?->teammates ?? [];
        if (is_string($teammates)) {
            $teammates = json_decode($teammates, true) ?? [];
        }

        $position = 'Participant';
        if ($teamName) {
            $position = 'Member';
            if (! empty($teammates) && is_array($teammates)) {
                foreach ($teammates as $t) {
                    if ((isset($t['user_id']) && $t['user_id'] == $this->id) || (isset($t['name']) && $t['name'] == $name)) {
                        if (($t['role'] ?? '') === 'Team Leader') {
                            $position = 'Team Leader';
                        }
                        break;
                    }
                }
                // If first in roster is this user, they are the leader
                if ($position === 'Member' && isset($teammates[0]) && (($teammates[0]['user_id'] ?? null) == $this->id || ($teammates[0]['name'] ?? null) == $name)) {
                    $position = 'Team Leader';
                }
            }
        }

        return [
            'id'              => $this->id,
            'name'            => $name,
            'profile_picture' => $this->youthProfile?->profile_picture ?? '',
            'contact'         => $contact,
            'email'           => $this->email,
            'purok'           => $purok,
            'barangay'        => $barangay,
            'team_name'       => $teamName,
            'position'        => $position,
            'teammates'       => $teammates,
            // Map attended_at or status to 'Attended' or 'Not Attended' for the frontend
            'status'          => ($this->pivot && ($this->pivot->attended_at || $this->pivot->status === 'Attended')) ? 'Attended' : 'Not Attended',
        ];
    }
}