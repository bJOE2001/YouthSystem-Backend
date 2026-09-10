<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
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
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'permissions' => $this->permissions ?? [],
            'is_root' => $this->isRootAdmin(),
            'can_scan' => (bool) $this->canScan(),
            'scholar_position' => $this->scholar?->scholarPosition ? [
                'id' => $this->scholar->scholarPosition->id,
                'name' => $this->scholar->scholarPosition->name,
                'can_scan' => (bool) $this->scholar->scholarPosition->can_scan,
            ] : null,
            'qr_code_token' => $this->qr_code_token,
            'scholar' => $this->ecesproScholar,
        ];
    }
}
