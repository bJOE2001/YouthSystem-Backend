<?php

namespace App\Actions\SkAdmin\YouthValidation;

use App\Models\YouthProfile;

class GetYouthRegistrationDetailsAction
{
    public function execute(YouthProfile $youthProfile): YouthProfile
    {
        $youthProfile->loadMissing('user');

        return $youthProfile;
    }
}
