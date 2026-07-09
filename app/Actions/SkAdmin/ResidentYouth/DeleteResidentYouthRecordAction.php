<?php

namespace App\Actions\SkAdmin\ResidentYouth;

use App\Models\YouthProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteResidentYouthRecordAction
{
    /**
     * @throws Throwable
     */
    public function execute(YouthProfile $youthProfile): void
    {
        DB::transaction(function () use ($youthProfile) {
            $user = $youthProfile->user;

            $attachedIdPath = $youthProfile->attached_id_path;

            $youthProfile->delete();

            if ($user) {
                $user->delete();
            }

            if ($attachedIdPath !== null) {
                Storage::disk('local')->delete($attachedIdPath);
            }
        });
    }
}
