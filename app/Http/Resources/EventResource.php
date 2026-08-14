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

        $dateFormatted = $end ? $start->format('M d, Y').' - '.$end->format('M d, Y') : $start->format('M d, Y');
        $timeFormatted = $this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : '';
        if ($this->end_time) {
            $timeFormatted .= ' - '.Carbon::parse($this->end_time)->format('g:i A');
        }

        $dateTime = trim($dateFormatted.' '.$timeFormatted);

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
            'status' => ucfirst(strtolower($this->status)),
            'rawStatus' => $this->status,
            'raw_status' => $this->status,
            'joined' => $user ? ($this->pivot ? true : $this->participants()->where('user_id', $user->id)->exists()) : false,
            'attended' => $attended,
            'isAttended' => $attended,
            'is_attended' => $attended,
            'attendanceStatus' => $attended ? 'Attended' : 'Not Attended',
            'attendance_status' => $attended ? 'Attended' : 'Not Attended',
            'hasCertificate' => $hasCertificate,
            'has_certificate' => $hasCertificate,
            'certificate' => $certificatePath ?: ($hasCertificate ? true : null),
            'certificates' => $hasCertificate,
            'certificatePath' => $certificatePath,
            'certificate_path' => $certificatePath,
            'certificateTemplatePath' => $this->certificate_template_path,
            'certificate_template_path' => $this->certificate_template_path,
            'certificateTemplateUrl' => $this->certificate_template_path ? url('storage/'.$this->certificate_template_path) : null,
            'certificate_template_url' => $this->certificate_template_path ? url('storage/'.$this->certificate_template_path) : null,
            'certificateSettings' => $this->certificate_settings,
            'certificate_settings' => $this->certificate_settings,
            'canDownloadCertificate' => $canDownloadCertificate,
            'can_download_certificate' => $canDownloadCertificate,
            'canDownload' => $canDownloadCertificate,
            'can_download' => $canDownloadCertificate,
            'certificateUrl' => $canDownloadCertificate ? url("/api/events/event_{$this->id}/certificate") : null,
            'certificate_url' => $canDownloadCertificate ? url("/api/events/event_{$this->id}/certificate") : null,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
