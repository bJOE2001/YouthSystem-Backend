<?php

namespace App\Http\Resources\SkAdmin;

use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkOfficialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->relationLoaded('user')
            ? $this->user
            : ($this->user_id ? User::with('youthProfile')->find($this->user_id) : null);

        if (! $user && ! empty($this->email)) {
            $user = User::where('email', $this->email)->with('youthProfile')->first();
        }

        $youthProfile = $user?->youthProfile;
        if (! $youthProfile && ! empty($this->email)) {
            $youthProfile = YouthProfile::whereHas('user', fn ($q) => $q->where('email', $this->email))->first();
        }

        $profilePicture = $youthProfile?->profile_picture;
        $qrCodeToken = $user ? $user->ensureQrToken() : null;
        $userId = $user?->id ?? $this->user_id;

        return [
            'id' => $this->id,
            'user_id' => $userId,
            'name' => $this->name,
            'profile_picture' => $profilePicture,
            'initials' => $this->initials,
            'barangay' => $this->barangay,
            'contact' => $this->contact ?? $youthProfile?->mobile_number,
            'email' => $this->email ?? $user?->email,
            'committee' => $this->committee,
            'position' => $this->position,
            'responsibilities' => $this->responsibilities,
            'term' => $this->term,
            'qr_code_token' => $qrCodeToken,
            'has_youth_profile' => $youthProfile !== null,
            'youth_profile' => $youthProfile ? [
                'id' => $youthProfile->id,
                'first_name' => $youthProfile->first_name,
                'middle_name' => $youthProfile->middle_name,
                'last_name' => $youthProfile->last_name,
                'suffix' => $youthProfile->suffix,
                'gender' => $youthProfile->gender,
                'birth_date' => $youthProfile->birth_date?->toDateString(),
                'age' => $youthProfile->birth_date?->age,
                'civil_status' => $youthProfile->civil_status,
                'mobile_number' => $youthProfile->mobile_number,
                'educational_attainment' => $youthProfile->educational_attainment,
                'course_strand' => $youthProfile->course_strand,
                'barangay' => $youthProfile->barangay,
                'purok_sitio' => $youthProfile->purok_sitio,
                'city' => $youthProfile->city,
                'province' => $youthProfile->province,
                'parents_contact_number' => $youthProfile->parents_contact_number,
                'father_name' => trim("{$youthProfile->father_first_name} {$youthProfile->father_middle_name} {$youthProfile->father_last_name}"),
                'mother_name' => trim("{$youthProfile->mother_first_name} {$youthProfile->mother_middle_name} {$youthProfile->mother_last_name}"),
                'status' => $youthProfile->status?->value ?? (string) $youthProfile->status,
            ] : null,
        ];
    }
}
