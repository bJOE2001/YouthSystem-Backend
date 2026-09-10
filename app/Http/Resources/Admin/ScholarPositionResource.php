<?php

namespace App\Http\Resources\Admin;

use App\Models\ScholarPosition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScholarPosition */
class ScholarPositionResource extends JsonResource
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
            'name' => $this->name,
            'can_scan' => (bool) $this->can_scan,
            'status' => $this->status,
            'scholars_count' => $this->whenCounted('scholars', $this->scholars_count, fn () => $this->scholars()->count()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
