<?php

namespace App\Http\Requests\YouthProfile;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateYouthProfileRequest extends YouthProfileRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return parent::authorize();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules(partial: true, requireAttachedId: false);
    }
}
