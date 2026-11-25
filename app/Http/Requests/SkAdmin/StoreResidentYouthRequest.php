<?php

namespace App\Http\Requests\SkAdmin;

use App\Http\Requests\Concerns\ValidatesYouthRegistrationFields;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class StoreResidentYouthRequest extends FormRequest
{
    use ValidatesYouthRegistrationFields;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Must be SK Admin. The role middleware already checks this, but we'll return true.
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        Log::error('Validation Failed in StoreResidentYouthRequest', [
            'errors' => $validator->errors()->toArray(),
            'request_data' => $this->except(['attachedId']),
            'request_files' => array_keys($this->allFiles()),
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->youthRegistrationRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->youthRegistrationMessages();
    }
}
