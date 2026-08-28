<?php

namespace App\Actions\SkAdmin\SkOfficials;

use App\Enums\UserRole;
use App\Models\SkOfficial;
use App\Models\User;

class DeleteSkOfficialAction
{
    public function execute(SkOfficial $skOfficial): void
    {
        $userId = $skOfficial->user_id;
        $email = $skOfficial->email;
        $skOfficial->delete();

        $user = null;
        if ($userId) {
            $user = User::find($userId);
        } elseif ($email) {
            $user = User::where('email', $email)->first();
        }

        // Revert back to Youth if they were an SkAdmin (preserving YouthProfile demographic data intact)
        if ($user && $user->role === UserRole::SkAdmin) {
            $user->role = UserRole::Youth;
            $user->save();
        }
    }
}
