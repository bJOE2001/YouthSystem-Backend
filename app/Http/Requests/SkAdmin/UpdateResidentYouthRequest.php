<?php

namespace App\Http\Requests\SkAdmin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateResidentYouthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $youthProfile = $this->route('youthProfile');
        $userId = $youthProfile ? $youthProfile->user_id : null;

        return [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'firstName' => ['required', 'string', 'max:100'],
            'middleName' => ['nullable', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Prefer not to say'])],
            'birthdate' => ['required', 'date', 'before:today'],
            'placeOfBirth' => ['required', 'string', 'max:255'],
            'mobileNumber' => ['required', 'string', 'max:30'],
            'fatherFirstName' => ['nullable', 'string', 'max:100'],
            'fatherMiddleName' => ['nullable', 'string', 'max:100'],
            'fatherLastName' => ['nullable', 'string', 'max:100'],
            'motherFirstName' => ['nullable', 'string', 'max:100'],
            'motherMiddleName' => ['nullable', 'string', 'max:100'],
            'motherLastName' => ['nullable', 'string', 'max:100'],
            'parentsContactNumber' => ['nullable', 'string', 'max:30'],
            'guardianFirstName' => ['nullable', 'string', 'max:100'],
            'guardianLastName' => ['nullable', 'string', 'max:100'],
            'guardianContactNumber' => ['nullable', 'string', 'max:30'],
            'currentlyAttendingSchool' => ['required', Rule::in(['yes', 'no'])],
            'seniorHighGraduate' => ['required', Rule::in(['yes', 'no'])],
            'educationalAttainment' => ['required', Rule::in([
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
            'courseStrand' => ['nullable', 'string', 'max:150'],
            'ethnicity' => ['nullable', 'string', 'max:100'],
            'religiousAffiliation' => ['nullable', 'string', 'max:100'],
            'hasDisability' => ['required', Rule::in(['yes', 'no'])],
            'overseasWorker' => ['required', Rule::in(['yes', 'no'])],
            'lgbtqMember' => ['required', Rule::in(['yes', 'no'])],
            'specialYouthSector' => ['nullable', Rule::in([
                'Out-of-school Youth',
                'Working Youth',
                'Youth with Special Needs',
                'Indigenous People Youth',
                'None',
            ])],
            'attachedId' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max('5mb')],
            'birthRegistered' => ['required', Rule::in(['yes', 'no'])],
            'civilStatus' => ['required', Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'soloParent' => ['required', Rule::in(['yes', 'no'])],
            'barangay' => ['required', 'string', 'max:100'],
            'purokSitio' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'zipcode' => ['required', 'string', 'max:10'],
        ];
    }
}
