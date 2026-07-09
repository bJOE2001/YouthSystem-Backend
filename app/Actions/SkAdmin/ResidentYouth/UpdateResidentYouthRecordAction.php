<?php

namespace App\Actions\SkAdmin\ResidentYouth;

use App\Actions\YouthProfile\UpdateYouthProfileAction;
use App\Models\YouthProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateResidentYouthRecordAction
{
    public function __construct(private UpdateYouthProfileAction $updateYouthProfileAction) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function execute(YouthProfile $youthProfile, array $data, ?UploadedFile $attachedId): YouthProfile
    {
        return DB::transaction(function () use ($youthProfile, $data, $attachedId) {
            $name = trim(implode(' ', array_filter([
                $data['firstName'],
                $data['middleName'] ?? null,
                $data['lastName'],
                $data['suffix'] ?? null,
            ])));

            $youthProfile->user->update([
                'name' => $name,
                'email' => $data['email'],
            ]);

            $mappedProfileData = [
                'first_name' => $data['firstName'],
                'middle_name' => $data['middleName'] ?? null,
                'last_name' => $data['lastName'],
                'suffix' => $data['suffix'] ?? null,
                'gender' => $data['gender'],
                'birth_date' => $data['birthdate'],
                'place_of_birth' => $data['placeOfBirth'],
                'mobile_number' => $data['mobileNumber'],
                'father_first_name' => $data['fatherFirstName'] ?? null,
                'father_middle_name' => $data['fatherMiddleName'] ?? null,
                'father_last_name' => $data['fatherLastName'] ?? null,
                'mother_first_name' => $data['motherFirstName'] ?? null,
                'mother_middle_name' => $data['motherMiddleName'] ?? null,
                'mother_last_name' => $data['motherLastName'] ?? null,
                'parents_contact_number' => $data['parentsContactNumber'] ?? null,
                'guardian_first_name' => $data['guardianFirstName'] ?? null,
                'guardian_last_name' => $data['guardianLastName'] ?? null,
                'guardian_contact_number' => $data['guardianContactNumber'] ?? null,
                'currently_attending_school' => $data['currentlyAttendingSchool'] === 'yes',
                'senior_high_graduate' => $data['seniorHighGraduate'] === 'yes',
                'educational_attainment' => $data['educationalAttainment'],
                'course_strand' => $data['courseStrand'] ?? null,
                'ethnicity' => $data['ethnicity'] ?? null,
                'religious_affiliation' => $data['religiousAffiliation'] ?? null,
                'has_disability' => $data['hasDisability'] === 'yes',
                'overseas_worker' => $data['overseasWorker'] === 'yes',
                'lgbtq_member' => $data['lgbtqMember'] === 'yes',
                'special_youth_sector' => $data['specialYouthSector'] ?? null,
                'birth_registered' => $data['birthRegistered'] === 'yes',
                'civil_status' => $data['civilStatus'],
                'solo_parent' => $data['soloParent'] === 'yes',
                'barangay' => $data['barangay'],
                'purok_sitio' => $data['purokSitio'],
                'city' => $data['city'],
                'province' => $data['province'],
                'postal_code' => $data['zipcode'],
            ];

            return $this->updateYouthProfileAction->execute($youthProfile, $mappedProfileData, $attachedId);
        });
    }
}
