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

        $frontendUrl = config('app.frontend_url') ?: (config('app.url') ?: 'http://localhost');
        $shareUrl = rtrim($frontendUrl, '/')."/activities/sport_{$this->id}";

        $isList = $request->routeIs('*.index') || $request->routeIs('public.*') || $request->routeIs('*.my');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'strategicDirection' => $this->when(! $isList, $this->strategic_direction),
            'startDate' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'endDate' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'time' => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
            'dateTime' => $dateTime,
            'location' => $this->location,
            // 'budgetAllocated' => $this->budget_allocated ? (float) $this->budget_allocated : null,
            // 'budgetUtilized' => $this->budget_utilized ? (float) $this->budget_utilized : null,
            'objective1' => $this->when(! $isList, $this->objective_1),
            'objective2' => $this->when(! $isList, $this->objective_2),
            'objective3' => $this->when(! $isList, $this->objective_3),
            'openToAll' => $this->open_to_all_barangays ?? true,
            'barangay' => $this->barangay,
            'status' => ucfirst(strtolower($this->status)),
            'joined' => $user ? ($this->pivot ? true : $this->participants()->where('user_id', $user->id)->exists()) : false,
            'attendanceStatus' => $this->when(! $isList, $attended ? 'Attended' : 'Not Attended'),
            'attendedAt' => $this->when(! $isList, $attendedAt),
            'hasCertificate' => $this->when(! $isList, $hasCertificate),
            'certificate' => $this->when(! $isList, $certificatePath ?: ($hasCertificate ? true : null)),
            'certificatePath' => $this->when(! $isList, $certificatePath),
            'certificateTemplatePath' => $this->when(! $isList, $this->certificate_template_path),
            'certificateTemplateUrl' => $this->when(! $isList, $this->certificate_template_path ? url('storage/'.$this->certificate_template_path) : null),
            'certificateSettings' => $this->when(! $isList, $this->certificate_settings),
            'canDownloadCertificate' => $this->when(! $isList, $canDownloadCertificate),
            'certificateUrl' => $this->when(! $isList, $certDownloadUrl),
            'shareUrl' => $this->when(! $isList, $shareUrl),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
