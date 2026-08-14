<?php

namespace App\Http\Resources\SkAdmin;

use App\Models\YouthProfile;
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
        // Officials are created from resident youth records, so their profile
        // picture lives on the linked youth profile (matched via email).
        $profilePicture = null;
        if (! empty($this->email)) {
            $profilePicture = YouthProfile::whereHas('user', function ($q) {
                $q->where('email', $this->email);
            })->value('profile_picture');
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'profile_picture' => $profilePicture,
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
