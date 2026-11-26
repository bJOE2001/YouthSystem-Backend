<?php

namespace App\Actions\YouthProfile;

use App\Models\YouthProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UpdateYouthProfileAction
{
    /**
     * Update only the explicitly provided youth profile fields without resetting
     * validation status or modifying approval metadata.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws Throwable
     */
    public function execute(
        YouthProfile $youthProfile,
        array $attributes,
        ?UploadedFile $attachedId,
        bool $resetToPending = false,
    ): YouthProfile {
        // Never allow ordinary profile updates to demote approved profiles or clear approval metadata.
        unset(
            $attributes['status'],
            $attributes['reviewed_by'],
            $attributes['reviewed_at'],
            $attributes['rejection_reason'],
        );

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
                if ($attributes !== []) {
                    $youthProfile->fill($attributes);
                }

                if ($newAttachedIdPath !== null) {
                    $youthProfile->attached_id_path = $newAttachedIdPath;
                }

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
            Storage::disk('public')->delete($previousAttachedIdPath);
        }

        return $youthProfile->refresh();
    }
}
