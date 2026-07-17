<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSportsProgramRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Program,Project,Activity',
            'strategicDirection' => 'required|string',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'startTime' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'budgetAllocated' => 'nullable|numeric|min:0',
            'budgetUtilized' => 'nullable|numeric|min:0',
            'objective1' => 'nullable|string',
            'objective2' => 'nullable|string',
            'objective3' => 'nullable|string',
            'status' => 'nullable|string|in:draft,upcoming,ongoing,completed,cancelled,Draft,Upcoming,Ongoing,Completed,Cancelled',
        ];
    }
}
