<?php

namespace App\Actions\SkAdmin\SkOfficials;

use App\Models\SkOfficial;

class GetSkOfficialDetailsAction
{
    public function execute(SkOfficial $skOfficial): SkOfficial
    {
        return $skOfficial;
    }
}
