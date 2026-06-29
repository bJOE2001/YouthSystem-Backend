<?php

namespace App\Http\Resources;

use App\Enums\YouthProfileStatus;
use App\Models\YouthProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin YouthProfile */
class YouthProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'email' => $this->whenLoaded('user', fn (): ?string => $this->user?->email),
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'age' => $this->birth_date?->age,
            'place_of_birth' => $this->place_of_birth,
            'mobile_number' => $this->mobile_number,
            'father_first_name' => $this->father_first_name,
            'father_middle_name' => $this->father_middle_name,
            'father_last_name' => $this->father_last_name,
            'mother_first_name' => $this->mother_first_name,
            'mother_middle_name' => $this->mother_middle_name,
            'mother_last_name' => $this->mother_last_name,
            'parents_contact_number' => $this->parents_contact_number,
            'guardian_first_name' => $this->guardian_first_name,
            'guardian_last_name' => $this->guardian_last_name,
            'guardian_contact_number' => $this->guardian_contact_number,
            'currently_attending_school' => $this->currently_attending_school,
            'senior_high_graduate' => $this->senior_high_graduate,
            'educational_attainment' => $this->educational_attainment,
            'course_strand' => $this->course_strand,
            'ethnicity' => $this->ethnicity,
            'religious_affiliation' => $this->religious_affiliation,
            'has_disability' => $this->has_disability,
            'overseas_worker' => $this->overseas_worker,
            'lgbtq_member' => $this->lgbtq_member,
            'special_youth_sector' => $this->special_youth_sector,
            'has_attached_id' => $this->attached_id_path !== null,
            'birth_registered' => $this->birth_registered,
            'civil_status' => $this->civil_status,
            'solo_parent' => $this->solo_parent,
            'barangay' => $this->barangay,
            'purok_sitio' => $this->purok_sitio,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'status' => $this->status->value,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'rejection_reason' => $this->when(
                $this->status === YouthProfileStatus::Rejected,
                $this->rejection_reason,
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
