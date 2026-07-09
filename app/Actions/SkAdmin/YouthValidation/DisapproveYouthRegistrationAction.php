<?php

namespace App\Actions\SkAdmin\YouthValidation;

use App\Enums\YouthProfileStatus;
use App\Models\YouthProfile;

class DisapproveYouthRegistrationAction
{
    public function execute(YouthProfile $youthProfile): YouthProfile
    {
        $youthProfile->update([
            'status' => YouthProfileStatus::Rejected->value,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return $youthProfile;
    }
}
