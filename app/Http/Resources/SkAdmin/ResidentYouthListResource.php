<?php

namespace App\Http\Resources\SkAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentYouthListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cleanLastName = trim(preg_replace('/\s+(Jr\.?|Sr\.?|I{1,3}|IV|V|VI|VII|VIII)\b/i', '', $this->last_name ?? ''));
        $cleanLastName = $cleanLastName ?: $this->last_name;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => trim(implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $cleanLastName,
                $this->suffix,
            ]))),
            'profile_picture' => $this->profile_picture,
            'contact' => $this->mobile_number,
            'email' => $this->user ? $this->user->email : '',
            'barangay' => $this->barangay,
            'purok' => $this->purok_sitio,
            'organization_id' => $this->organization_id,
            'organization_name' => $this->organization?->name ?? 'None',
            'status' => $this->organization?->name ?? 'None',
        ];
    }
}
