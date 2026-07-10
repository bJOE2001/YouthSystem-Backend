<?php

namespace App\Actions\SkAdmin\SkOfficials;

use App\Models\SkOfficial;

class DeleteSkOfficialAction
{
    public function execute(SkOfficial $skOfficial): void
    {
        $email = $skOfficial->email;
        $skOfficial->delete();

        if ($email) {
            $user = \App\Models\User::where('email', $email)->first();
            // Revert back to Youth if they were an SkAdmin (don't downgrade actual Admins)
            if ($user && $user->role === \App\Enums\UserRole::SkAdmin) {
                $user->role = \App\Enums\UserRole::Youth;
                $user->save();
            }
        }
    }
}
