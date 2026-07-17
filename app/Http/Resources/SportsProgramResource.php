<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SportsProgramResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $start = $this->start_date ? Carbon::parse($this->start_date) : null;
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;

        $dateFormatted = '';
        if ($start) {
            $dateFormatted = $end ? $start->format('M d, Y').' - '.$end->format('M d, Y') : $start->format('M d, Y');
        }

        $timeFormatted = $this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : '';
        $dateTime = trim($dateFormatted.' '.$timeFormatted);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'strategicDirection' => $this->strategic_direction,
            'startDate' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'endDate' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'time' => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
            'dateTime' => $dateTime,
            'location' => $this->location,
            'budgetAllocated' => $this->budget_allocated ? (float) $this->budget_allocated : null,
            'budgetUtilized' => $this->budget_utilized ? (float) $this->budget_utilized : null,
            'objective1' => $this->objective_1,
            'objective2' => $this->objective_2,
            'objective3' => $this->objective_3,
            'status' => $this->status,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
