<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'category' => $this->category,
            'subject' => $this->subject,
            'message' => $this->message,
            'is_anonymous' => (bool) $this->is_anonymous,
            'isAnonymous' => (bool) $this->is_anonymous,
            'event_id' => $this->event_id,
            'eventId' => $this->event_id,
            'event_name' => $this->event?->name ?? $this->event?->title,
            'eventName' => $this->event?->name ?? $this->event?->title,
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
