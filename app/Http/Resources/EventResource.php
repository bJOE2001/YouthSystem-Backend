<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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

        $dateFormatted = $end ? $start->format('M d, Y') . ' - ' . $end->format('M d, Y') : $start->format('M d, Y');
        $timeFormatted = $this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : '';
        if ($this->end_time) {
            $timeFormatted .= ' - ' . Carbon::parse($this->end_time)->format('g:i A');
        }

        $dateTime = trim($dateFormatted . ' ' . $timeFormatted);

        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        $attended = false;
        if ($user) {
            if ($this->pivot) {
                $attended = ! empty($this->pivot->attended_at);
            } else {
                $pivot = $this->participants()->where('user_id', $user->id)->first()?->pivot;
                $attended = $pivot ? ! empty($pivot->attended_at) : false;
            }
        }

        $certificatePath = $this->certificate_template_path ?? ($this->pivot?->certificate_path ?? null);
        $hasCertificate = ! empty($certificatePath);
        $isCompleted = strtolower((string) $this->status) === 'completed';
        $canDownloadCertificate = (bool) ($attended && $hasCertificate && $isCompleted);

        $frontendUrl = config('app.frontend_url') ?: (config('app.url') ?: 'http://localhost');
        $shareUrl = rtrim($frontendUrl, '/') . "/activities/event_{$this->id}";

        $isList = $request->routeIs('*.index') || $request->routeIs('public.*') || $request->routeIs('*.my');

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
            'hasNoAllocatedBudget' => $this->when(! $isList, (bool) $this->has_no_allocated_budget),
            'noBudgetReason' => $this->when(! $isList, $this->no_budget_reason),
            'performanceIndicator' => $this->when(! $isList, $this->performance_indicator),
            'description' => $this->when(! $isList, $this->performance_indicator), // frontend alias
            'primaryObjective1' => $this->when(! $isList, $this->primary_objective_1),
            'primaryObjective2' => $this->when(! $isList, $this->primary_objective_2),
            'primaryObjective3' => $this->when(! $isList, $this->primary_objective_3),
            'status' => ucfirst(strtolower($this->status)),
            'joined' => $user ? ($this->pivot ? true : $this->participants()->where('user_id', $user->id)->exists()) : false,
            'attendanceStatus' => $this->when(! $isList, $attended ? 'Attended' : 'Not Attended'),
            'hasCertificate' => $this->when(! $isList, $hasCertificate),
            'certificate' => $this->when(! $isList, $certificatePath ?: ($hasCertificate ? true : null)),
            'certificatePath' => $this->when(! $isList, $certificatePath),
            'certificateTemplatePath' => $this->when(! $isList, $this->certificate_template_path),
            'certificateTemplateUrl' => $this->when(! $isList, $this->certificate_template_path ? url('storage/' . $this->certificate_template_path) : null),
            'certificateSettings' => $this->when(! $isList, $this->certificate_settings),
            'canDownloadCertificate' => $this->when(! $isList, $canDownloadCertificate),
            'certificateUrl' => $this->when(! $isList, $canDownloadCertificate ? url("/api/events/event_{$this->id}/certificate") : null),
            'shareUrl' => $this->when(! $isList, $shareUrl),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
