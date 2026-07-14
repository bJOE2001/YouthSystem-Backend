<?php

namespace App\Http\Requests\Event;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $merge = [];

        if ($this->has('status') && is_string($this->status)) {
            $merge['status'] = strtolower($this->status);
        }

        $nullableFields = ['endDate', 'endTime', 'location', 'noBudgetReason', 'budgetAllocated', 'budgetUtilized'];
        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'aipReferenceCode' => ['sometimes', 'string', 'max:255'],
            'ppaClassification' => ['sometimes', 'string', 'max:255'],
            'centerOfParticipation' => ['sometimes', 'string', 'max:255'],
            'sustainableDevelopmentGoal' => ['sometimes', 'string', 'max:255'],
            'startDate' => ['sometimes', 'date'],
            'endDate' => ['nullable', 'date'],
            'startTime' => ['sometimes', 'date_format:H:i'],
            'endTime' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'hasNoAllocatedBudget' => ['nullable', 'boolean'],
            'noBudgetReason' => ['required_if:hasNoAllocatedBudget,true', 'nullable', 'string', 'max:255'],
            'budgetAllocated' => ['nullable', 'numeric', 'min:0'],
            'budgetUtilized' => ['nullable', 'numeric', 'min:0'],
            'performanceIndicator' => ['sometimes', 'string'],
            'primaryObjective1' => ['sometimes', 'string'],
            'primaryObjective2' => ['sometimes', 'string'],
            'primaryObjective3' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:draft,upcoming,ongoing,completed,cancelled'],
        ];
    }
}
