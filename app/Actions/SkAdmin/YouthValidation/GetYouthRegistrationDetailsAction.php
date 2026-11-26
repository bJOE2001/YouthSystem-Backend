<?php

namespace App\Actions\SkAdmin\YouthValidation;

use App\Models\YouthValidation;

class GetYouthRegistrationDetailsAction
{
    public function execute(YouthValidation $youthValidation): YouthValidation
    {
        return $youthValidation;
    }
}
