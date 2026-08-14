<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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

        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        $attended = false;
        $attendedAt = null;
        if ($user) {
            if ($this->pivot) {
                $attended = ! empty($this->pivot->attended_at);
                $attendedAt = $this->pivot->attended_at ? Carbon::parse($this->pivot->attended_at)->toISOString() : null;
            } else {
                $pivot = $this->participants()->where('user_id', $user->id)->first()?->pivot;
                if ($pivot) {
                    $attended = ! empty($pivot->attended_at);
                    $attendedAt = $pivot->attended_at ? Carbon::parse($pivot->attended_at)->toISOString() : null;
                }
            }
        }

        $certificatePath = $this->certificate_template_path ?? ($this->pivot?->certificate_path ?? null);
        $hasCertificate = ! empty($certificatePath);
        $isCompleted = strtolower((string) $this->status) === 'completed';
        $canDownloadCertificate = (bool) ($attended && $hasCertificate && $isCompleted);
        $certDownloadUrl = $canDownloadCertificate ? url("/api/events/sport_{$this->id}/certificate") : null;

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
            'openToAll' => $this->open_to_all_barangays ?? true,
            'barangay' => $this->barangay,
            'status' => ucfirst(strtolower($this->status)),
            'rawStatus' => $this->status,
            'raw_status' => $this->status,
            'joined' => $user ? ($this->pivot ? true : $this->participants()->where('user_id', $user->id)->exists()) : false,
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
