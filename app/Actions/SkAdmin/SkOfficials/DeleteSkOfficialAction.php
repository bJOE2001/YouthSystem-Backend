<?php

namespace App\Actions\SkAdmin\SkOfficials;

use App\Models\SkOfficial;

class DeleteSkOfficialAction
{
    public function execute(SkOfficial $skOfficial): void
    {
        $skOfficial->delete();
    }
}
