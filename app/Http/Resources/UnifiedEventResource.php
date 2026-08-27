<?php

namespace App\Http\Resources;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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

        $start = Carbon::parse($this->start_date);
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;

        $dateFormatted = $end ? $start->format('M d, Y').' - '.$end->format('M d, Y') : $start->format('M d, Y');
        $timeFormatted = $this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : '';
        if ($isEvent && $this->end_time) {
            $timeFormatted .= ' - '.Carbon::parse($this->end_time)->format('g:i A');
        }

        $dateTime = trim($dateFormatted.' '.$timeFormatted);

        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        $joined = false;
        $attended = false;
        $attendedAt = null;

        if ($user) {
            if ($this->pivot) {
                $joined = true;
                $attended = ! empty($this->pivot->attended_at);
                $attendedAt = $this->pivot->attended_at ? Carbon::parse($this->pivot->attended_at)->toISOString() : null;
            } else {
                $pivot = $this->participants()->where('user_id', $user->id)->first()?->pivot;
                if ($pivot) {
                    $joined = true;
                    $attended = ! empty($pivot->attended_at);
                    $attendedAt = $pivot->attended_at ? Carbon::parse($pivot->attended_at)->toISOString() : null;
                }
            }
        }

        $certificatePath = $this->certificate_template_path ?? ($this->pivot?->certificate_path ?? null);
        $hasCertificate = ! empty($certificatePath);
        $isCompleted = strtolower((string) $this->status) === 'completed';
        $canDownloadCertificate = (bool) ($attended && $hasCertificate && $isCompleted);
        $unifiedId = $isEvent ? 'event_'.$this->id : 'sport_'.$this->id;
        $certDownloadUrl = $canDownloadCertificate ? url("/api/events/{$unifiedId}/certificate") : null;

        return [
            'id' => $unifiedId,
            'originalId' => $this->id,
            'source' => $isEvent ? 'Event' : 'Sports Program',
            'type' => $isEvent ? 'Event' : 'Sports Program',
            'activityType' => $isEvent ? 'Event' : 'Sports Program',
            'name' => $this->name,
            'aipReferenceCode' => $isEvent ? $this->aip_reference_code : null,
            'ppaClassification' => $isEvent ? $this->ppa_classification : $this->type,
            'category' => $isEvent ? $this->ppa_classification : $this->type, // frontend alias
            'centerOfParticipation' => $isEvent ? $this->center_of_participation : null,
            'sustainableDevelopmentGoal' => $isEvent ? $this->sustainable_development_goal : null,
            'startDate' => $this->start_date ? Carbon::parse($this->start_date)->format('Y-m-d') : null,
            'endDate' => $this->end_date ? Carbon::parse($this->end_date)->format('Y-m-d') : null,
            'startTime' => $this->start_time,
            'endTime' => $isEvent ? $this->end_time : null,
            'time' => $timeFormatted ?: ($this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : null),
            'dateTime' => $dateTime,
            'location' => $this->location,
            'barangay' => $this->barangay ?? $this->location ?? null,
            'openToAll' => ! $isEvent ? (bool) ($this->open_to_all_barangays ?? false) : (bool) ($this->open_to_all_barangays ?? true),
            'open_to_all_barangays' => ! $isEvent ? (bool) ($this->open_to_all_barangays ?? false) : (bool) ($this->open_to_all_barangays ?? true),
            'hasNoAllocatedBudget' => $isEvent ? (bool) $this->has_no_allocated_budget : false,
            'noBudgetReason' => $isEvent ? $this->no_budget_reason : null,
            'budgetAllocated' => $this->budget_allocated ? (float) $this->budget_allocated : null,
            'budgetUtilized' => $this->budget_utilized ? (float) $this->budget_utilized : null,
            'performanceIndicator' => $isEvent ? $this->performance_indicator : $this->strategic_direction,
            'description' => $isEvent ? $this->performance_indicator : $this->strategic_direction, // frontend alias
            'primaryObjective1' => $isEvent ? $this->primary_objective_1 : $this->objective_1,
            'primaryObjective2' => $isEvent ? $this->primary_objective_2 : $this->objective_2,
            'primaryObjective3' => $isEvent ? $this->primary_objective_3 : $this->objective_3,
            'objective1' => $isEvent ? $this->primary_objective_1 : $this->objective_1,
            'objective2' => $isEvent ? $this->primary_objective_2 : $this->objective_2,
            'objective3' => $isEvent ? $this->primary_objective_3 : $this->objective_3,
            'status' => ucfirst(strtolower($this->status)),
            'rawStatus' => $this->status,
            'raw_status' => $this->status,
            'joined' => $joined,
            'attended' => $attended,
            'isAttended' => $attended,
            'is_attended' => $attended,
            'attendanceStatus' => $attended ? 'Attended' : 'Not Attended',
            'attendance_status' => $attended ? 'Attended' : 'Not Attended',
            'attendedAt' => $attendedAt,
            'attended_at' => $attendedAt,
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
            'certificateUrl' => $certDownloadUrl,
            'certificate_url' => $certDownloadUrl,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
