<?php

namespace App\Http\Resources\Admin;

use App\Enums\UserRole;
use App\Models\SkOfficial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangayLibraryResource extends JsonResource
{
    /**
     * Cache of barangays that have an active SK Admin during the request lifecycle.
     *
     * @var array<string>|null
     */
    protected static ?array $skAdminBarangays = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (static::$skAdminBarangays === null) {
            $skAdminEmails = User::where('role', UserRole::SkAdmin)->pluck('email');
            static::$skAdminBarangays = SkOfficial::whereIn('email', $skAdminEmails)
                ->whereNotNull('barangay')
                ->pluck('barangay')
                ->unique()
                ->all();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'has_sk_admin' => in_array($this->name, static::$skAdminBarangays, true),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
