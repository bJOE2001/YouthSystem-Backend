<?php

namespace App\Actions\SkAdmin\SkOfficials;

use App\Enums\UserRole;
use App\Models\SkOfficial;
use App\Models\User;

class CreateSkOfficialAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): SkOfficial
    {
        $userId = $data['user_id'];
        $user = User::with('youthProfile')->findOrFail($userId);
        $youthProfile = $user->youthProfile;

        $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $youthProfile?->first_name,
            $youthProfile?->middle_name,
            $youthProfile?->last_name,
            $youthProfile?->suffix,
        ]))));
        if (empty($name)) {
            $name = $user->name;
        }

        $email = $youthProfile?->email ?? $user->email;
        $contact = $youthProfile?->mobile_number;
        $barangay = $youthProfile?->barangay;

        $parts = preg_split('/\s+/', trim($name));
        $initials = count($parts) >= 2
            ? strtoupper(substr($parts[0], 0, 1).substr(end($parts), 0, 1))
            : strtoupper(substr($name, 0, 2));

        $skOfficial = SkOfficial::create([
            'user_id' => $userId,
            'name' => $name,
            'initials' => $initials,
            'barangay' => $barangay,
            'contact' => $contact,
            'email' => $email,
            'committee' => $data['committee'] ?? null,
            'position' => $data['position'] ?? 'SK Official',
            'responsibilities' => $data['responsibilities'] ?? null,
            'term' => $data['term'] ?? '2023 - 2025',
        ]);

        if ($user->role !== UserRole::Admin) {
            $user->role = UserRole::SkAdmin;
            $user->save();
        }

        return $skOfficial->load(['user.youthProfile']);
    }
}
