<?php

namespace App\Actions\YouthProfile;

use App\Enums\YouthProfileStatus;
use App\Models\YouthProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UpdateYouthProfileAction
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws Throwable
     */
    public function execute(
        YouthProfile $youthProfile,
        array $attributes,
        ?UploadedFile $attachedId,
    ): YouthProfile {
        if ($attributes === [] && $attachedId === null) {
            return $youthProfile;
        }

        $previousAttachedIdPath = $youthProfile->attached_id_path;
        $newAttachedIdPath = $attachedId?->store('youth-identification', 'local');

        if ($newAttachedIdPath === false) {
            throw new RuntimeException('The identification file could not be stored.');
        }

        try {
            DB::transaction(function () use ($youthProfile, $attributes, $newAttachedIdPath): void {
                $youthProfile->fill($attributes);

                if ($newAttachedIdPath !== null) {
                    $youthProfile->attached_id_path = $newAttachedIdPath;
                }

                $youthProfile->status = YouthProfileStatus::Pending;
                $youthProfile->reviewed_by = null;
                $youthProfile->reviewed_at = null;
                $youthProfile->rejection_reason = null;
                $youthProfile->save();
            });
        } catch (Throwable $exception) {
            if ($newAttachedIdPath !== null) {
                Storage::disk('local')->delete($newAttachedIdPath);
            }

            throw $exception;
        }

        if ($newAttachedIdPath !== null && $previousAttachedIdPath !== null) {
            Storage::disk('local')->delete($previousAttachedIdPath);
        }

        return $youthProfile->refresh();
    }
}
