<?php

namespace App\Http\Resources\SkAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ResidentYouthDetailsResource extends JsonResource
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
            'name' => trim(implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $cleanLastName,
                $this->suffix,
            ]))),
            'firstName' => $this->first_name,
            'middleName' => $this->middle_name,
            'lastName' => $cleanLastName,
            'suffix' => $this->suffix,
            'gender' => $this->gender,
            'birthdate' => $this->birth_date ? $this->birth_date->format('F j, Y') : null,
            'birthdateRaw' => $this->birth_date ? $this->birth_date->format('Y-m-d') : null,
            'age' => $this->birth_date ? $this->birth_date->age : null,
            'placeOfBirth' => $this->place_of_birth,
            'contact' => $this->mobile_number,
            'mobileNumber' => $this->mobile_number,
            'facebookAccount' => $this->facebook_account,
            'email' => $this->user ? $this->user->email : null,
            'fatherFirstName' => $this->father_first_name,
            'fatherMiddleName' => $this->father_middle_name,
            'fatherLastName' => $this->father_last_name,
            'motherFirstName' => $this->mother_first_name,
            'motherMiddleName' => $this->mother_middle_name,
            'motherLastName' => $this->mother_last_name,
            'parentsContactNumber' => $this->parents_contact_number,
            'guardianFirstName' => $this->guardian_first_name,
            'guardianLastName' => $this->guardian_last_name,
            'guardianContactNumber' => $this->guardian_contact_number,
            'currentlyAttendingSchool' => $this->currently_attending_school ? 'Yes' : 'No',
            'seniorHighGraduate' => $this->senior_high_graduate ? 'Yes' : 'No',
            'educationalAttainment' => $this->educational_attainment,
            'courseStrand' => $this->course_strand,
            'ethnicity' => $this->ethnicity,
            'religiousAffiliation' => $this->religious_affiliation,
            'hasDisability' => $this->has_disability ? 'Yes' : 'No',
            'overseasWorker' => $this->overseas_worker ? 'Yes' : 'No',
            'lgbtqMember' => $this->lgbtq_member ? 'Yes' : 'No',
            'specialYouthSector' => $this->special_youth_sector,
            'attachedId' => $this->attached_id_path ? 'View ID' : null,
            'attachedIdImage' => $this->attached_id_path ? Storage::disk('public')->url($this->attached_id_path) : null,
            'attachedIdUrl' => $this->attached_id_path ? Storage::disk('public')->url($this->attached_id_path) : null,
            'birthRegistered' => $this->birth_registered ? 'Yes' : 'No',
            'civilStatus' => $this->civil_status,
            'soloParent' => $this->solo_parent ? 'Yes' : 'No',
            'barangay' => $this->barangay,
            'purok' => $this->purok_sitio,
            'purokSitio' => $this->purok_sitio,
            'city' => $this->city,
            'province' => $this->province,
            'zipcode' => $this->postal_code,
            'postalCode' => $this->postal_code,
            'organization_id' => $this->organization_id,
            'organization_name' => $this->organization?->name ?? 'None',
            'status' => $this->organization?->name ?? 'None',

            // Assuming bookings and events might be loaded later, default to empty arrays
            'bookingHistory' => $this->whenLoaded('bookings', function () {
                // Return mapped bookings here if relation exists
                return [];
            }, []),
            'eventHistory' => $this->whenLoaded('events', function () {
                // Return mapped events here if relation exists
                return [];
            }, []),
        ];
    }
}
