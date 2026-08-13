<?php

namespace App\Actions\SkAdmin\YouthValidation;

use App\Enums\UserStatus;
use App\Enums\YouthProfileStatus;
use App\Mail\YouthValidatedEmail;
use App\Models\YouthProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ApproveYouthRegistrationAction
{
    public function execute(YouthProfile $youthProfile): YouthProfile
    {
        return DB::transaction(function () use ($youthProfile): YouthProfile {
            $youthProfile->update([
                'status' => YouthProfileStatus::Approved->value,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            $user = $youthProfile->user;

            if ($user) {
                $plainPassword = $youthProfile->birth_date
                    ? $youthProfile->birth_date->format('mdy')
                    : 'password';

                $user->update([
                    'status' => UserStatus::Active->value,
                    'password' => Hash::make($plainPassword),
                    'email_verified_at' => now(),
                ]);

                Mail::to($user->email)->send(new YouthValidatedEmail($user, $plainPassword));
            }

            return $youthProfile;
        });
    }
}
