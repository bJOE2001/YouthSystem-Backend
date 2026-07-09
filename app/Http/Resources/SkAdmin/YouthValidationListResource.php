<?php

namespace App\Http\Resources\SkAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class YouthValidationListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status ? ucfirst($this->status->value) : 'Pending';
        if ($status === 'Rejected') {
            $status = 'Disapproved'; // Aligning with the frontend expectation
        }

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
            'age' => $this->birth_date ? $this->birth_date->age : null,
            'purok' => $this->purok_sitio,
            'status' => $status,
        ];
    }
}
