<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust to your auth/policy setup
    }

    public function rules(): array
    {
        return [
            'updates' => ['required', 'array', 'min:1', 'max:2000'],
            'updates.*.id' => ['required', 'integer', 'exists:examinations,id'],
            'updates.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'], // adjust range
            'updates.*.status' => ['required', 'string', 'in:Pending,Passed,Failed'],
        ];
    }
}
