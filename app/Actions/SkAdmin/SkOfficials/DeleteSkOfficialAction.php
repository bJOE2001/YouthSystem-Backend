<?php

namespace App\Actions\SkAdmin\SkOfficials;

use App\Enums\UserRole;
use App\Models\SkOfficial;
use App\Models\User;

class DeleteSkOfficialAction
{
    public function execute(SkOfficial $skOfficial): void
    {
        $email = $skOfficial->email;
        $skOfficial->delete();

        if ($email) {
            $user = User::where('email', $email)->first();
            // Revert back to Youth if they were an SkAdmin (don't downgrade actual Admins)
            if ($user && $user->role === UserRole::SkAdmin) {
                $user->role = UserRole::Youth;
                $user->save();
            }
        }
    }
}
