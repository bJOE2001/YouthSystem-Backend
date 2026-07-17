<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $start = Carbon::parse($this->start_date);
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;

        $dateFormatted = $end ? $start->format('M d, Y').' - '.$end->format('M d, Y') : $start->format('M d, Y');
        $timeFormatted = $this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : '';
        if ($this->end_time) {
            $timeFormatted .= ' - '.Carbon::parse($this->end_time)->format('g:i A');
        }

        $dateTime = trim($dateFormatted.' '.$timeFormatted);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'aipReferenceCode' => $this->aip_reference_code,
            'ppaClassification' => $this->ppa_classification,
            'category' => $this->ppa_classification, // frontend alias
            'centerOfParticipation' => $this->center_of_participation,
            'sustainableDevelopmentGoal' => $this->sustainable_development_goal,
            'startDate' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'endDate' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'startTime' => $this->start_time,
            'endTime' => $this->end_time,
            'time' => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
            'dateTime' => $dateTime,
            'location' => $this->location,
            'hasNoAllocatedBudget' => (bool) $this->has_no_allocated_budget,
            'noBudgetReason' => $this->no_budget_reason,
            'budgetAllocated' => $this->budget_allocated ? (float) $this->budget_allocated : null,
            'budgetUtilized' => $this->budget_utilized ? (float) $this->budget_utilized : null,
            'performanceIndicator' => $this->performance_indicator,
            'description' => $this->performance_indicator, // frontend alias
            'primaryObjective1' => $this->primary_objective_1,
            'primaryObjective2' => $this->primary_objective_2,
            'primaryObjective3' => $this->primary_objective_3,
            'status' => $this->status,
            'joined' => auth('sanctum')->check() ? $this->participants()->where('user_id', auth('sanctum')->id())->exists() : false,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
