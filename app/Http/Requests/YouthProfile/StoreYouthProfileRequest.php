<?php

namespace App\Http\Requests\YouthProfile;

use App\Models\YouthProfile;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreYouthProfileRequest extends YouthProfileRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return parent::authorize()
            && $this->user()->can('create', YouthProfile::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules(partial: false, requireAttachedId: true);
    }
}
