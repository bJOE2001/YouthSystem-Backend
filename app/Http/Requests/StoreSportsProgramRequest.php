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

    protected function prepareForValidation()
    {
        $merge = [];

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
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Program,Project,Activity',
            'strategicDirection' => 'required|string',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'startTime' => 'nullable|date_format:H:i,H:i:s',
            'location' => 'nullable|string|max:255',
            // 'budgetAllocated' => 'nullable|numeric|min:0',
            // 'budgetUtilized' => 'nullable|numeric|min:0',
            'objective1' => 'nullable|string',
            'objective2' => 'nullable|string',
            'objective3' => 'nullable|string',
            'openToAll' => 'nullable|boolean',
            'open_to_all_barangays' => 'nullable|boolean',
            'status' => 'nullable|string|in:draft,upcoming,ongoing,completed,cancelled,Draft,Upcoming,Ongoing,Completed,Cancelled',
        ];
    }
}
