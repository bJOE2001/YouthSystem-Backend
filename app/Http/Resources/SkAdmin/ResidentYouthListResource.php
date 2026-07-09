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
        return [
            'id' => $this->id,
            'name' => trim(implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
                $this->suffix,
            ]))),
            'contact' => $this->mobile_number,
            'email' => $this->user ? $this->user->email : 'No Email',
            'purok' => $this->purok_sitio,
            'status' => $this->status ? ucfirst($this->status->value) : 'Non-Sinag',
        ];
    }
}
