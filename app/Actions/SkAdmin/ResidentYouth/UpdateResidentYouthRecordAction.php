<?php

namespace App\Actions\SkAdmin\ResidentYouth;

use App\Actions\YouthProfile\UpdateYouthProfileAction;
use App\Models\YouthProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class UpdateResidentYouthRecordAction
{
    public function __construct(private UpdateYouthProfileAction $updateYouthProfileAction) {}

    /**
     * Update only the explicitly submitted fields for an existing resident youth
     * without resetting approval status, reviewer metadata, or unrelated fields.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function execute(YouthProfile $youthProfile, array $data, ?UploadedFile $attachedId): YouthProfile
    {
        return DB::transaction(function () use ($youthProfile, $data, $attachedId) {
            $mappedProfileData = [];

            $directMap = [
                'firstName' => 'first_name',
                'middleName' => 'middle_name',
                'gender' => 'gender',
                'birthdate' => 'birth_date',
                'placeOfBirth' => 'place_of_birth',
                'mobileNumber' => 'mobile_number',
                'fatherFirstName' => 'father_first_name',
                'fatherMiddleName' => 'father_middle_name',
                'fatherLastName' => 'father_last_name',
                'motherFirstName' => 'mother_first_name',
                'motherMiddleName' => 'mother_middle_name',
                'motherLastName' => 'mother_last_name',
                'parentsContactNumber' => 'parents_contact_number',
                'guardianFirstName' => 'guardian_first_name',
                'guardianLastName' => 'guardian_last_name',
                'guardianContactNumber' => 'guardian_contact_number',
                'educationalAttainment' => 'educational_attainment',
                'courseStrand' => 'course_strand',
                'ethnicity' => 'ethnicity',
                'religiousAffiliation' => 'religious_affiliation',
                'specialYouthSector' => 'special_youth_sector',
                'civilStatus' => 'civil_status',
                'barangay' => 'barangay',
                'purokSitio' => 'purok_sitio',
                'city' => 'city',
                'province' => 'province',
                'zipcode' => 'postal_code',
            ];

            foreach ($directMap as $inputKey => $column) {
                if (array_key_exists($inputKey, $data)) {
                    $mappedProfileData[$column] = $data[$inputKey];
                }
            }

            if (array_key_exists('lastName', $data)) {
                $cleanLastName = trim(preg_replace('/\s+(Jr\.?|Sr\.?|I{1,3}|IV|V|VI|VII|VIII)\b/i', '', (string) $data['lastName']));
                $mappedProfileData['last_name'] = $cleanLastName ?: $data['lastName'];
            }

            if (array_key_exists('suffix', $data)) {
                $mappedProfileData['suffix'] = ! empty($data['suffix']) && $data['suffix'] !== 'None'
                    ? $data['suffix']
                    : null;
            }

            if (array_key_exists('facebookAccount', $data) || array_key_exists('facebook_account', $data)) {
                $mappedProfileData['facebook_account'] = array_key_exists('facebookAccount', $data)
                    ? $data['facebookAccount']
                    : $data['facebook_account'];
            }

            $booleanMap = [
                'currentlyAttendingSchool' => 'currently_attending_school',
                'seniorHighGraduate' => 'senior_high_graduate',
                'hasDisability' => 'has_disability',
                'overseasWorker' => 'overseas_worker',
                'lgbtqMember' => 'lgbtq_member',
                'birthRegistered' => 'birth_registered',
                'soloParent' => 'solo_parent',
            ];

            foreach ($booleanMap as $inputKey => $column) {
                if (array_key_exists($inputKey, $data)) {
                    $mappedProfileData[$column] = $data[$inputKey] === 'yes';
                }
            }

            $userUpdates = [];
            $nameChanged = array_key_exists('firstName', $data)
                || array_key_exists('middleName', $data)
                || array_key_exists('lastName', $data)
                || array_key_exists('suffix', $data);

            if ($nameChanged) {
                $fullName = trim(implode(' ', array_filter([
                    $mappedProfileData['first_name'] ?? $youthProfile->first_name,
                    array_key_exists('middle_name', $mappedProfileData) ? $mappedProfileData['middle_name'] : $youthProfile->middle_name,
                    $mappedProfileData['last_name'] ?? $youthProfile->last_name,
                    array_key_exists('suffix', $mappedProfileData) ? $mappedProfileData['suffix'] : $youthProfile->suffix,
                ])));
                $userUpdates['name'] = $fullName;
            }

            if (array_key_exists('email', $data)) {
                $userUpdates['email'] = $data['email'];
            }

            $previousEmail = $youthProfile->user?->email;

            if ($userUpdates !== [] && $youthProfile->user) {
                $youthProfile->user->update($userUpdates);
            }

            $updatedProfile = $this->updateYouthProfileAction->execute(
                $youthProfile,
                $mappedProfileData,
                $attachedId,
                false,
            );

            // Sync active SK Official record only with resulting/updated values when relevant fields changed
            if (Schema::hasTable('sk_officials') && ($nameChanged || array_key_exists('email', $data) || array_key_exists('mobileNumber', $data) || array_key_exists('barangay', $data))) {
                $resultingName = $userUpdates['name'] ?? trim(implode(' ', array_filter([
                    $updatedProfile->first_name,
                    $updatedProfile->middle_name,
                    $updatedProfile->last_name,
                    $updatedProfile->suffix,
                ])));
                $parts = preg_split('/\s+/', trim($resultingName));
                $initials = count($parts) >= 2
                    ? strtoupper(substr($parts[0], 0, 1).substr(end($parts), 0, 1))
                    : strtoupper(substr($resultingName, 0, 2));

                DB::table('sk_officials')
                    ->where(function ($q) use ($updatedProfile, $previousEmail) {
                        $q->where('user_id', $updatedProfile->user_id);
                        if (! empty($previousEmail)) {
                            $q->orWhere('email', $previousEmail);
                        }
                    })
                    ->update([
                        'name' => $resultingName,
                        'initials' => $initials,
                        'email' => $updatedProfile->user?->email ?? $previousEmail,
                        'contact' => $updatedProfile->mobile_number,
                        'barangay' => $updatedProfile->barangay,
                        'updated_at' => now(),
                    ]);
            }

            return $updatedProfile;
        });
    }
}
