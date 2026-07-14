<?php

namespace App\Http\Requests\Event;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'aipReferenceCode' => ['required', 'string', 'max:255'],
            'ppaClassification' => ['required', 'string', 'max:255'],
            'centerOfParticipation' => ['required', 'string', 'max:255'],
            'sustainableDevelopmentGoal' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'hasNoAllocatedBudget' => ['nullable', 'boolean'],
            'noBudgetReason' => ['required_if:hasNoAllocatedBudget,true', 'nullable', 'string', 'max:255'],
            'budgetAllocated' => ['nullable', 'numeric', 'min:0'],
            'budgetUtilized' => ['nullable', 'numeric', 'min:0'],
            'performanceIndicator' => ['required', 'string'],
            'primaryObjective1' => ['required', 'string'],
            'primaryObjective2' => ['required', 'string'],
            'primaryObjective3' => ['required', 'string'],
            'status' => ['required', 'in:draft,upcoming,ongoing,completed,cancelled'],
        ];
    }
}
