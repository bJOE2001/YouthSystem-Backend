<?php

namespace App\Http\Requests\SkAdmin;

use App\Http\Requests\Concerns\ValidatesYouthRegistrationFields;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateResidentYouthRequest extends FormRequest
{
    use ValidatesYouthRegistrationFields;

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
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
                function (string $attribute, mixed $value, Closure $fail) use ($userId): void {
                    if (is_string($value)) {
                        $query = User::whereRaw('LOWER(email) = ?', [strtolower($value)]);
                        if ($userId !== null) {
                            $query->where('id', '!=', $userId);
                        }
                        if ($query->exists()) {
                            $fail('This email is already used. You cannot reuse the same email address.');
                        }
                    }
                },
            ],
            'firstName' => ['sometimes', 'required', 'string', 'max:100'],
            'middleName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'lastName' => ['sometimes', 'required', 'string', 'max:100'],
            'suffix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'gender' => ['sometimes', 'required', Rule::in(['Male', 'Female', 'Prefer not to say'])],
            'birthdate' => ['sometimes', 'required', 'date', 'before:today'],
            'placeOfBirth' => ['sometimes', 'required', 'string', 'max:255'],
            'mobileNumber' => ['sometimes', 'required', 'string', 'max:30'],
            'facebookAccount' => ['sometimes', 'nullable', 'string', 'max:255'],
            'facebook_account' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fatherFirstName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'fatherMiddleName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'fatherLastName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'motherFirstName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'motherMiddleName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'motherLastName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'parentsContactNumber' => ['sometimes', 'nullable', 'string', 'max:30'],
            'guardianFirstName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'guardianLastName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'guardianContactNumber' => ['sometimes', 'nullable', 'string', 'max:30'],
            'currentlyAttendingSchool' => ['sometimes', 'required', Rule::in(['yes', 'no'])],
            'seniorHighGraduate' => ['sometimes', 'required', Rule::in(['yes', 'no'])],
            'educationalAttainment' => ['sometimes', 'required', Rule::in([
                'Elementary Level',
                'Elementary Graduate',
                'High School Level',
                'High School Graduate',
                'Senior High School Level',
                'Senior High School Graduate',
                'College Level',
                'College Graduate',
                'Vocational Graduate',
                'Masters Level',
                'Masters Graduate',
                'Doctorate Level',
                'Doctorate Graduate',
            ])],
            'courseStrand' => ['sometimes', 'nullable', 'string', 'max:150'],
            'ethnicity' => ['sometimes', 'nullable', 'string', 'max:100'],
            'religiousAffiliation' => ['sometimes', 'nullable', 'string', 'max:100'],
            'hasDisability' => ['sometimes', 'required', Rule::in(['yes', 'no'])],
            'overseasWorker' => ['sometimes', 'required', Rule::in(['yes', 'no'])],
            'lgbtqMember' => ['sometimes', 'required', Rule::in(['yes', 'no'])],
            'specialYouthSector' => ['sometimes', 'nullable', Rule::in([
                'Out-of-school Youth',
                'Working Youth',
                'Youth with Special Needs',
                'Youth in Conflict with the Law',
                'Indigenous People Youth',
                'Indigenous Youth',
                'None',
            ])],
            'attachedId' => ['sometimes', 'nullable', Rule::when($this->hasFile('attachedId'), File::types(['jpg', 'jpeg', 'png', 'pdf'])->max('20mb'))],
            'birthRegistered' => ['sometimes', 'required', Rule::in(['yes', 'no'])],
            'civilStatus' => ['sometimes', 'required', Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'soloParent' => ['sometimes', 'required', Rule::in(['yes', 'no'])],
            'barangay' => ['sometimes', 'required', 'string', 'max:100'],
            'purokSitio' => ['sometimes', 'required', 'string', 'max:100'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'province' => ['sometimes', 'required', 'string', 'max:100'],
            'zipcode' => ['sometimes', 'required', 'string', 'max:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->youthRegistrationMessages();
    }
}
