<?php

namespace App\Actions\SkAdmin\YouthValidation;

use App\Actions\YouthProfile\FindMatchingYouthProfileAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\YouthProfileStatus;
use App\Exceptions\DuplicateYouthRecordException;
use App\Mail\YouthValidatedEmail;
use App\Models\Barangay;
use App\Models\Purok;
use App\Models\User;
use App\Models\YouthProfile;
use App\Models\YouthValidation;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ApproveYouthRegistrationAction
{
    public function __construct(
        private FindMatchingYouthProfileAction $findMatchingYouthProfileAction,
    ) {}

    public function execute(YouthValidation $youthValidation): YouthValidation
    {
        return DB::transaction(function () use ($youthValidation): YouthValidation {
            $validation = YouthValidation::query()
                ->whereKey($youthValidation->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $validation->status !== YouthProfileStatus::Pending,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'This youth registration has already been processed.'
            );

            $normalizedEmail = strtolower(trim($validation->email));
            if (User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['This email is already used. You cannot reuse the same email address.'],
                ]);
            }

            if (! $validation->birth_date) {
                throw new RuntimeException('Cannot approve youth registration without a valid birth date.');
            }

            $matchingData = [
                'first_name' => $validation->first_name,
                'middle_name' => $validation->middle_name,
                'last_name' => $validation->last_name,
                'suffix' => $validation->suffix,
                'gender' => $validation->gender,
                'birth_date' => $validation->birth_date->format('Y-m-d'),
                'mobile_number' => $validation->mobile_number,
                'barangay' => $validation->barangay,
                'purok_sitio' => $validation->purok_sitio,
                'city' => $validation->city,
                'province' => $validation->province,
            ];

            if ($this->findMatchingYouthProfileAction->execute($matchingData, ignoreYouthValidationId: $validation->id) !== null) {
                throw new DuplicateYouthRecordException;
            }

            $name = trim(implode(' ', array_filter([
                $validation->first_name,
                $validation->middle_name,
                $validation->last_name,
                $validation->suffix,
            ])));

            $plainPassword = $validation->birth_date->format('mdy');

            $user = User::create([
                'name' => $name,
                'email' => $normalizedEmail,
                'password' => Hash::make($plainPassword),
                'role' => UserRole::Youth->value,
                'status' => UserStatus::Active->value,
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]);

            $barangayName = $validation->barangay;
            $purokName = trim((string) $validation->purok_sitio);

            if (! empty($barangayName) && ! empty($purokName)) {
                $barangayModel = Barangay::where('name', $barangayName)->first();
                Purok::firstOrCreate(
                    [
                        'name' => $purokName,
                        'barangay' => $barangayName,
                    ],
                    [
                        'barangay_id' => $barangayModel?->id,
                        'user_id' => Auth::id(),
                    ]
                );
            }

            $reviewedAt = now();
            $reviewedBy = Auth::id();

            $youthProfile = new YouthProfile([
                'first_name' => $validation->first_name,
                'middle_name' => $validation->middle_name,
                'last_name' => $validation->last_name,
                'suffix' => $validation->suffix,
                'gender' => $validation->gender,
                'birth_date' => $validation->birth_date,
                'place_of_birth' => $validation->place_of_birth,
                'mobile_number' => $validation->mobile_number,
                'father_first_name' => $validation->father_first_name,
                'father_middle_name' => $validation->father_middle_name,
                'father_last_name' => $validation->father_last_name,
                'mother_first_name' => $validation->mother_first_name,
                'mother_middle_name' => $validation->mother_middle_name,
                'mother_last_name' => $validation->mother_last_name,
                'parents_contact_number' => $validation->parents_contact_number,
                'guardian_first_name' => $validation->guardian_first_name,
                'guardian_last_name' => $validation->guardian_last_name,
                'guardian_contact_number' => $validation->guardian_contact_number,
                'currently_attending_school' => $validation->currently_attending_school,
                'senior_high_graduate' => $validation->senior_high_graduate,
                'educational_attainment' => $validation->educational_attainment,
                'course_strand' => $validation->course_strand,
                'ethnicity' => $validation->ethnicity,
                'religious_affiliation' => $validation->religious_affiliation,
                'has_disability' => $validation->has_disability,
                'overseas_worker' => $validation->overseas_worker,
                'lgbtq_member' => $validation->lgbtq_member,
                'special_youth_sector' => $validation->special_youth_sector,
                'birth_registered' => $validation->birth_registered,
                'civil_status' => $validation->civil_status,
                'solo_parent' => $validation->solo_parent,
                'barangay' => $validation->barangay,
                'purok_sitio' => $validation->purok_sitio,
                'city' => $validation->city,
                'province' => $validation->province,
                'postal_code' => $validation->postal_code,
                'status' => YouthProfileStatus::Approved->value,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => $reviewedAt,
            ]);
            $youthProfile->attached_id_path = $validation->attached_id_path;
            $user->youthProfile()->save($youthProfile);

            $validation->update([
                'status' => YouthProfileStatus::Approved->value,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => $reviewedAt,
                'rejection_reason' => null,
                'youth_profile_id' => $youthProfile->id,
            ]);

            Mail::to($user->email)->send(new YouthValidatedEmail($user, $plainPassword));

            if (! empty($youthProfile->mobile_number)) {
                $smsMessage = "Welcome to TCYSDO! Your account has been approved. Email: {$user->email}, Temp Password: {$plainPassword}. Please login and change your password.";
                app(SmsService::class)->send(
                    $youthProfile->mobile_number,
                    $smsMessage,
                    $user,
                    'youth_validation'
                );
            }

            return $validation->refresh();
        });
    }
}
