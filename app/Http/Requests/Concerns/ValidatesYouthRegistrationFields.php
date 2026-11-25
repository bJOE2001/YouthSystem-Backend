<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;
use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

trait ValidatesYouthRegistrationFields
{
    private const NAME_REGEX = '/^(?=.*\p{L})[\p{L}\s.\'\-,\/]+$/u';

    private const MOBILE_REGEX = '/^\d{1,11}$/';

    protected function prepareForValidation(): void
    {
        $trimKeys = [
            'email',
            'firstName',
            'middleName',
            'lastName',
            'placeOfBirth',
            'mobileNumber',
            'fatherFirstName',
            'fatherMiddleName',
            'fatherLastName',
            'motherFirstName',
            'motherMiddleName',
            'motherLastName',
            'parentsContactNumber',
            'guardianFirstName',
            'guardianLastName',
            'guardianContactNumber',
            'courseStrand',
            'purokSitio',
        ];

        $normalized = [];

        foreach ($trimKeys as $key) {
            if ($this->has($key) && is_string($this->input($key))) {
                $value = trim($this->input($key));
                if ($key === 'email') {
                    $value = strtolower($value);
                }
                $normalized[$key] = $value === '' ? null : $value;
            }
        }

        if ($this->has('suffix') && $this->input('suffix') === 'None') {
            $normalized['suffix'] = null;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function youthRegistrationRules(): array
    {
        $minBirthYear = max(1900, now()->year - 120);

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && User::whereRaw('LOWER(email) = ?', [strtolower($value)])->exists()) {
                        $fail('This email is already used. You cannot reuse the same email address.');
                    }
                },
            ],
            'firstName' => ['required', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'middleName' => ['nullable', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'lastName' => ['required', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'suffix' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Prefer not to say'])],
            'birthdate' => [
                'required',
                'date',
                'before_or_equal:today',
                'after_or_equal:'.$minBirthYear.'-01-01',
            ],
            'placeOfBirth' => ['required', 'string', 'min:2', 'max:255', 'regex:/\p{L}/u'],
            'mobileNumber' => ['required', 'string', 'regex:'.self::MOBILE_REGEX],
            'fatherFirstName' => ['nullable', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'fatherMiddleName' => ['nullable', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'fatherLastName' => ['nullable', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'motherFirstName' => ['nullable', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'motherMiddleName' => ['nullable', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'motherLastName' => ['nullable', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'parentsContactNumber' => ['nullable', 'string', 'regex:'.self::MOBILE_REGEX],
            'guardianFirstName' => ['nullable', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'guardianLastName' => ['nullable', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'guardianContactNumber' => ['nullable', 'string', 'regex:'.self::MOBILE_REGEX],
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
            'attachedId' => ['required', File::types(['jpg', 'jpeg', 'png'])->max('20mb')],
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

    /**
     * @return array<string, string>
     */
    protected function youthRegistrationMessages(): array
    {
        $mobileMsg = 'Please enter digits only (maximum of 11 digits).';

        $nameLabels = [
            'firstName' => 'First Name',
            'middleName' => 'Middle Name',
            'lastName' => 'Last Name',
            'fatherFirstName' => "Father's First Name",
            'fatherMiddleName' => "Father's Middle Name",
            'fatherLastName' => "Father's Last Name",
            'motherFirstName' => "Mother's Maiden First Name",
            'motherMiddleName' => "Mother's Maiden Middle Name",
            'motherLastName' => "Mother's Maiden Last Name",
            'guardianFirstName' => 'Guardian First Name',
            'guardianLastName' => 'Guardian Last Name',
        ];

        $messages = [
            'email.unique' => 'This email is already used. You cannot reuse the same email address.',
            'email.email' => 'Please enter a valid email address (e.g. name@example.com).',
            'mobileNumber.regex' => $mobileMsg,
            'parentsContactNumber.regex' => $mobileMsg,
            'guardianContactNumber.regex' => $mobileMsg,
            'placeOfBirth.min' => 'Please enter a valid place of birth.',
            'placeOfBirth.regex' => 'Please enter a valid place of birth.',
            'birthdate.before_or_equal' => 'Birthdate cannot be in the future.',
            'birthdate.after_or_equal' => 'Please enter a valid birthdate.',
            'attachedId.mimes' => 'Please select a JPG or PNG image file.',
        ];

        foreach ($nameLabels as $field => $label) {
            $messages["{$field}.regex"] = "Please enter a valid {$label} (letters only, no numbers).";
        }

        return $messages;
    }
}
