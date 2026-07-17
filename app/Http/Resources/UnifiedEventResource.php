<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\SportsProgram;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnifiedEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isEvent = $this->resource instanceof Event;
        $isSport = $this->resource instanceof SportsProgram;

        $start = Carbon::parse($this->start_date);
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;

        $dateFormatted = $end ? $start->format('M d, Y').' - '.$end->format('M d, Y') : $start->format('M d, Y');
        $timeFormatted = $this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : '';
        if ($this->end_time) {
            $timeFormatted .= ' - '.Carbon::parse($this->end_time)->format('g:i A');
        }

        $dateTime = trim($dateFormatted.' '.$timeFormatted);

        return [
            'id' => $isEvent ? 'event_'.$this->id : 'sport_'.$this->id,
            'originalId' => $this->id,
            'source' => $isEvent ? 'Event' : 'Sports Program',
            'name' => $this->name,
            'ppaClassification' => $isEvent ? $this->ppa_classification : $this->type,
            'category' => $isEvent ? $this->ppa_classification : $this->type, // frontend alias
            'centerOfParticipation' => $isEvent ? $this->center_of_participation : null,
            'sustainableDevelopmentGoal' => $isEvent ? $this->sustainable_development_goal : null,
            'startDate' => $this->start_date ? Carbon::parse($this->start_date)->format('Y-m-d') : null,
            'endDate' => $this->end_date ? Carbon::parse($this->end_date)->format('Y-m-d') : null,
            'startTime' => $this->start_time,
            'endTime' => $this->end_time,
            'time' => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
            'dateTime' => $dateTime,
            'location' => $this->location,
            'hasNoAllocatedBudget' => $isEvent ? (bool) $this->has_no_allocated_budget : false,
            'noBudgetReason' => $isEvent ? $this->no_budget_reason : null,
            'budgetAllocated' => $this->budget_allocated ? (float) $this->budget_allocated : null,
            'budgetUtilized' => $this->budget_utilized ? (float) $this->budget_utilized : null,
            'performanceIndicator' => $isEvent ? $this->performance_indicator : $this->strategic_direction,
            'description' => $isEvent ? $this->performance_indicator : $this->strategic_direction, // frontend alias
            'primaryObjective1' => $isEvent ? $this->primary_objective_1 : $this->objective_1,
            'primaryObjective2' => $isEvent ? $this->primary_objective_2 : $this->objective_2,
            'primaryObjective3' => $isEvent ? $this->primary_objective_3 : $this->objective_3,
            'status' => $this->status,
            'joined' => auth()->check() ? $this->participants()->where('user_id', auth()->id())->exists() : false,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
