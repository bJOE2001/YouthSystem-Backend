<?php

namespace App\Http\Resources;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnifiedEventResource extends JsonResource
{
    protected static array $userJoinedEvents = [];

    protected static array $userJoinedSports = [];

    public static function clearCache(): void
    {
        static::$userJoinedEvents = [];
        static::$userJoinedSports = [];
    }

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
        $pivotRecord = $this->pivot ?? null;

        if ($user) {
            if ($this->pivot) {
                $joined = true;
                $attended = ! empty($this->pivot->attended_at);
                $attendedAt = $this->pivot->attended_at ? Carbon::parse($this->pivot->attended_at)->toISOString() : null;
            } else {
                $userKey = (string) $user->id;

                if ($isEvent) {
                    if (! isset(static::$userJoinedEvents[$userKey])) {
                        static::$userJoinedEvents[$userKey] = DB::table('event_user')
                            ->where('user_id', $user->id)
                            ->get()
                            ->keyBy('event_id')
                            ->toArray();
                    }
                    if (isset(static::$userJoinedEvents[$userKey][$this->id])) {
                        $pivotRecord = static::$userJoinedEvents[$userKey][$this->id];
                    }
                } else {
                    if (! isset(static::$userJoinedSports[$userKey])) {
                        static::$userJoinedSports[$userKey] = DB::table('sports_program_user')
                            ->where('user_id', $user->id)
                            ->get()
                            ->keyBy('sports_program_id')
                            ->toArray();
                    }
                    if (isset(static::$userJoinedSports[$userKey][$this->id])) {
                        $pivotRecord = static::$userJoinedSports[$userKey][$this->id];
                    }
                }

                if ($pivotRecord) {
                    $joined = true;
                    $attended = ! empty($pivotRecord->attended_at);
                    $attendedAt = $pivotRecord->attended_at ? Carbon::parse($pivotRecord->attended_at)->toISOString() : null;
                }
            }
        }

        $certificatePath = $this->certificate_template_path ?? ($pivotRecord->certificate_path ?? null);
        $hasCertificate = ! empty($certificatePath);
        $isCompleted = strtolower((string) $this->status) === 'completed';
        $canDownloadCertificate = (bool) ($attended && $hasCertificate && $isCompleted);
        $unifiedId = $isEvent ? 'event_'.$this->id : 'sport_'.$this->id;
        $certDownloadUrl = $canDownloadCertificate ? url("/api/events/{$unifiedId}/certificate") : null;

        $isList = $request->routeIs('*.index') || $request->routeIs('public.*') || $request->routeIs('*.my');

        return [
            'id' => $unifiedId,
            'originalId' => $this->id,
            'source' => $isEvent ? 'Event' : 'Sports Program',
            'type' => $isEvent ? 'Event' : 'Sports Program',
            'activityType' => $isEvent ? 'Event' : 'Sports Program',
            'name' => $this->name,
            'aipReferenceCode' => $this->when(! $isList, $isEvent ? $this->aip_reference_code : null),
            'ppaClassification' => $isEvent ? $this->ppa_classification : $this->type,
            'category' => $isEvent ? $this->ppa_classification : $this->type, // frontend alias
            'centerOfParticipation' => $this->when(! $isList, $isEvent ? $this->center_of_participation : null),
            'sustainableDevelopmentGoal' => $this->when(! $isList, $isEvent ? $this->sustainable_development_goal : null),
            'startDate' => $this->start_date ? Carbon::parse($this->start_date)->format('Y-m-d') : null,
            'endDate' => $this->end_date ? Carbon::parse($this->end_date)->format('Y-m-d') : null,
            'startTime' => $this->start_time,
            'endTime' => $isEvent ? $this->end_time : null,
            'time' => $timeFormatted ?: ($this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : null),
            'dateTime' => $dateTime,
            'location' => $this->location,
            'barangay' => $this->barangay ?? $this->location ?? null,
            'openToAll' => ! $isEvent ? (bool) ($this->open_to_all_barangays ?? false) : (bool) ($this->open_to_all_barangays ?? true),
            'hasNoAllocatedBudget' => $this->when(! $isList, $isEvent ? (bool) $this->has_no_allocated_budget : false),
            'noBudgetReason' => $this->when(! $isList, $isEvent ? $this->no_budget_reason : null),
            'performanceIndicator' => $this->when(! $isList, $isEvent ? $this->performance_indicator : $this->strategic_direction),
            'description' => $this->when(! $isList, $isEvent ? $this->performance_indicator : $this->strategic_direction), // frontend alias
            'primaryObjective1' => $this->when(! $isList, $isEvent ? $this->primary_objective_1 : $this->objective_1),
            'primaryObjective2' => $this->when(! $isList, $isEvent ? $this->primary_objective_2 : $this->objective_2),
            'primaryObjective3' => $this->when(! $isList, $isEvent ? $this->primary_objective_3 : $this->objective_3),
            'status' => ucfirst(strtolower($this->status)),
            'joined' => $joined,
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
            'shareUrl' => $this->when(! $isList, rtrim(config('app.frontend_url') ?: (config('app.url') ?: 'http://localhost'), '/')."/activities/{$unifiedId}"),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
