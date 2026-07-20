<?php

namespace App\Http\Requests\YouthProfile;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

abstract class YouthProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->isActive()
            && ($user->hasRole(UserRole::Youth) || $user->hasRole(UserRole::SkAdmin));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function profileRules(bool $partial, bool $requireAttachedId): array
    {
        $presence = $partial ? 'sometimes' : 'required';
        $attachedIdPresence = $requireAttachedId ? 'required' : 'sometimes';

        return [
            'first_name' => [$presence, 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => [$presence, 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'gender' => [$presence, Rule::in(['Male', 'Female', 'Prefer not to say'])],
            'birth_date' => [$presence, 'date', 'before:today'],
            'place_of_birth' => [$presence, 'string', 'max:255'],
            'mobile_number' => [$presence, 'string', 'max:30'],
            'father_first_name' => ['nullable', 'string', 'max:100'],
            'father_middle_name' => ['nullable', 'string', 'max:100'],
            'father_last_name' => ['nullable', 'string', 'max:100'],
            'mother_first_name' => ['nullable', 'string', 'max:100'],
            'mother_middle_name' => ['nullable', 'string', 'max:100'],
            'mother_last_name' => ['nullable', 'string', 'max:100'],
            'parents_contact_number' => ['nullable', 'string', 'max:30'],
            'guardian_first_name' => ['nullable', 'string', 'max:100'],
            'guardian_last_name' => ['nullable', 'string', 'max:100'],
            'guardian_contact_number' => ['nullable', 'string', 'max:30'],
            'currently_attending_school' => [$presence, 'boolean'],
            'senior_high_graduate' => [$presence, 'boolean'],
            'educational_attainment' => [$presence, Rule::in([
                'Elementary Level',
                'Elementary Graduate',
                'High School Level',
                'High School Graduate',
                'Senior High School Level',
                'Senior High School Graduate',
                'College Level',
                'College Graduate',
                'Vocational Graduate',
            ])],
            'course_strand' => ['nullable', 'string', 'max:150'],
            'ethnicity' => ['nullable', 'string', 'max:100'],
            'religious_affiliation' => ['nullable', 'string', 'max:100'],
            'has_disability' => [$presence, 'boolean'],
            'overseas_worker' => [$presence, 'boolean'],
            'lgbtq_member' => [$presence, 'boolean'],
            'special_youth_sector' => ['nullable', Rule::in([
                'Out-of-school Youth',
                'Working Youth',
                'Youth with Special Needs',
                'Indigenous People Youth',
                'None',
            ])],
            'attached_id' => [$attachedIdPresence, File::types(['jpg', 'jpeg', 'png', 'pdf'])->max('5mb')],
            'birth_registered' => [$presence, 'boolean'],
            'civil_status' => [$presence, Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'solo_parent' => [$presence, 'boolean'],
            'barangay' => [$presence, 'string', 'max:100'],
            'purok_sitio' => [$presence, 'string', 'max:100'],
            'city' => [$presence, 'string', 'max:100'],
            'province' => [$presence, 'string', 'max:100'],
            'postal_code' => [$presence, 'string', 'max:10'],
        ];
    }
}
