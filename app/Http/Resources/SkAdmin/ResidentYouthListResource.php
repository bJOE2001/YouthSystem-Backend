<?php

namespace App\Http\Resources\SkAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentYouthListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cleanLastName = trim(preg_replace('/\s+(Jr\.?|Sr\.?|I{1,3}|IV|V|VI|VII|VIII)\b/i', '', $this->last_name ?? ''));
        $cleanLastName = $cleanLastName ?: $this->last_name;

        $age = null;
        if ($this->birth_date) {
            $age = \Carbon\Carbon::parse($this->birth_date)->age;
        }

        $user = $this->user;
        $youthStatus = 'active';

        if ($age !== null && $age >= 31) {
            $youthStatus = 'overage';
        } elseif ($user) {
            $oneMonthAgo = now()->subDays(30);
            if ($user->last_login_at) {
                if (\Carbon\Carbon::parse($user->last_login_at)->lt($oneMonthAgo)) {
                    $youthStatus = 'inactive';
                }
            } else {
                // If never logged in, active for the first 30 days, then becomes inactive
                if ($user->created_at && \Carbon\Carbon::parse($user->created_at)->lt($oneMonthAgo)) {
                    $youthStatus = 'inactive';
                }
            }
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => trim(implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $cleanLastName,
                $this->suffix,
            ]))),
            'profile_picture' => $this->profile_picture,
            'contact' => $this->mobile_number,
            'email' => $user ? $user->email : '',
            'barangay' => $this->barangay,
            'purok' => $this->purok_sitio,
            'organization_id' => $this->organization_id,
            'organization_name' => $this->organization?->name ?? 'None',
            'status' => $this->organization?->name ?? 'None',
            'youth_status' => $youthStatus,
            'age' => $age,
            'last_login_at' => $user?->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->toIso8601String() : null,
        ];
    }
}
