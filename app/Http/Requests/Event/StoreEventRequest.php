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

        if ($this->has('startTime')) {
            $raw = trim((string) $this->startTime);
            if ($raw === '') {
                $merge['startTime'] = null;
            } elseif (preg_match('/^(\d{1,2}):(\d{2})/', $raw, $m)) {
                $merge['startTime'] = str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2];
            } elseif ($parsed = strtotime($raw)) {
                $merge['startTime'] = date('H:i', $parsed);
            }
        }

        if ($this->has('endTime')) {
            $raw = trim((string) $this->endTime);
            if ($raw === '') {
                $merge['endTime'] = null;
            } elseif (preg_match('/^(\d{1,2}):(\d{2})/', $raw, $m)) {
                $merge['endTime'] = str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2];
            } elseif ($parsed = strtotime($raw)) {
                $merge['endTime'] = date('H:i', $parsed);
            }
        }

        $nullableFields = ['endDate', 'location', 'noBudgetReason', 'budgetAllocated', 'budgetUtilized', 'maxParticipants', 'max_participants'];
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
            'startTime' => ['required', 'date_format:H:i,H:i:s'],
            'endTime' => ['nullable', 'date_format:H:i,H:i:s'],
            'location' => ['nullable', 'string', 'max:255'],
            'hasNoAllocatedBudget' => ['nullable', 'boolean'],
            'noBudgetReason' => ['required_if:hasNoAllocatedBudget,true', 'nullable', 'string', 'max:255'],
            'budgetAllocated' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'budgetUtilized' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'performanceIndicator' => ['required', 'string'],
            'primaryObjective1' => ['required', 'string'],
            'primaryObjective2' => ['required', 'string'],
            'primaryObjective3' => ['required', 'string'],
            'status' => ['required', 'in:draft,upcoming,ongoing,completed,cancelled'],
            'openToAll' => ['nullable', 'boolean'],
            'openToAllBarangays' => ['nullable', 'boolean'],
            'maxParticipants' => ['nullable', 'integer', 'min:1'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
