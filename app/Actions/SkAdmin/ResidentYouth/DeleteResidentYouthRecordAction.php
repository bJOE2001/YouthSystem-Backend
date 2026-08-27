<?php

namespace App\Actions\SkAdmin\ResidentYouth;

use App\Models\YouthProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
                if (Schema::hasTable('sports_program_user')) {
                    DB::table('sports_program_user')->where('user_id', $user->id)->delete();
                }

                if (Schema::hasTable('event_user')) {
                    DB::table('event_user')->where('user_id', $user->id)->delete();
                }

                if (Schema::hasTable('ecespro_scholars')) {
                    DB::table('ecespro_scholars')->where('user_id', $user->id)->delete();
                }

                if (Schema::hasTable('feedbacks')) {
                    DB::table('feedbacks')->where('user_id', $user->id)->update(['user_id' => null]);
                }

                $user->delete();
            }

            if ($attachedIdPath !== null) {
                Storage::disk('local')->delete($attachedIdPath);
            }
        });
    }
}
