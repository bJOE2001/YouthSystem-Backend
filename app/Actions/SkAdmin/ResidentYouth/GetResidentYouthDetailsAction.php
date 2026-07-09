<?php

namespace App\Actions\SkAdmin\ResidentYouth;

use App\Models\YouthProfile;

class GetResidentYouthDetailsAction
{
    public function execute(YouthProfile $youthProfile): YouthProfile
    {
        return $youthProfile->loadMissing('user');
    }
}
