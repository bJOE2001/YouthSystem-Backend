<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requestedBy' => $this->user->name ?? 'Unknown',
            'email' => $this->user->email ?? 'Unknown',
            'facility' => $this->facility->name ?? 'Unknown',
            'location' => $this->facility->location ?? 'Unknown',
            'dateTime' => Carbon::parse($this->date)->format('Y-m-d').' '.Carbon::parse($this->start_time)->format('h:i A').' - '.Carbon::parse($this->end_time)->format('h:i A'),
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'purpose' => $this->purpose,
            'status' => $this->status,
        ];
    }
}
