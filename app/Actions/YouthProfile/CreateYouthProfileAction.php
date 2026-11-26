<?php

namespace App\Actions\YouthProfile;

use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CreateYouthProfileAction
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws Throwable
     */
    public function execute(User $user, array $attributes, UploadedFile $attachedId): YouthProfile
    {
        $attachedIdPath = $attachedId->store('youth-identification', 'local');

        if ($attachedIdPath === false) {
            throw new RuntimeException('The identification file could not be stored.');
        }

        try {
            return DB::transaction(function () use ($user, $attributes, $attachedIdPath): YouthProfile {
                $youthProfile = new YouthProfile($attributes);
                $youthProfile->attached_id_path = $attachedIdPath;

                $user->youthProfile()->save($youthProfile);

                return $youthProfile;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($attachedIdPath);

            throw $exception;
        }
    }
}
