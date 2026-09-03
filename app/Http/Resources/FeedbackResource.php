<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resolvedEventId = $this->event_id ?? $this->sports_program_id;
        $resolvedEventName = $this->event?->name ?? $this->sportsProgram?->name ?? $this->event?->title ?? $this->sportsProgram?->title;

        $isSk = false;
        if ($this->target === 'sk') {
            $isSk = true;
        } elseif ($this->event) {
            $role = $this->event->user?->role;
            $roleStr = strtolower($role instanceof \BackedEnum ? $role->value : (string)$role);
            if ($roleStr === 'sk_admin' || $roleStr === 'sk' || $this->event->scope === 'barangay' || !empty($this->event->barangay)) {
                $isSk = true;
            }
        } elseif ($this->sportsProgram) {
            $role = $this->sportsProgram->user?->role;
            $roleStr = strtolower($role instanceof \BackedEnum ? $role->value : (string)$role);
            if ($roleStr === 'sk_admin' || $roleStr === 'sk' || (!empty($this->sportsProgram->barangay) && strtolower($this->sportsProgram->barangay) !== 'all')) {
                $isSk = true;
            }
        }

        $resolvedBarangay = $this->barangay
            ?? $this->event?->barangay
            ?? $this->sportsProgram?->barangay
            ?? $this->event?->user?->youthProfile?->barangay
            ?? $this->sportsProgram?->user?->youthProfile?->barangay;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'target' => $this->target ?? ($isSk ? 'sk' : 'admin'),
            'barangay' => $resolvedBarangay,
            'is_sk' => $isSk,
            'category' => $this->category,
            'subject' => $this->subject,
            'message' => $this->message,
            'is_anonymous' => (bool) $this->is_anonymous,
            'isAnonymous' => (bool) $this->is_anonymous,
            'event_id' => $resolvedEventId,
            'eventId' => $resolvedEventId,
            'sports_program_id' => $this->sports_program_id,
            'sportsProgramId' => $this->sports_program_id,
            'event_name' => $resolvedEventName,
            'eventName' => $resolvedEventName,
            'user' => $this->when(! $this->is_anonymous && $this->user, function () {
                $profile = $this->user->youthProfile;

                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'first_name' => $profile?->first_name,
                    'last_name' => $profile?->last_name,
                    'email' => $this->user->email,
                    'profile_picture' => $profile?->profile_picture ? url('storage/'.$profile->profile_picture) : null,
                ];
            }),
            'created_at' => $this->created_at,
            'date' => $this->created_at?->toIso8601String(),
        ];
    }
}