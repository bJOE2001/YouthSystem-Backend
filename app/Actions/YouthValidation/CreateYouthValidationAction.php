<?php

namespace App\Actions\YouthValidation;

use App\Enums\YouthProfileStatus;
use App\Models\YouthValidation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CreateYouthValidationAction
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws Throwable
     */
    public function execute(string $email, array $attributes, UploadedFile $attachedId): YouthValidation
    {
        $attachedIdPath = $attachedId->store('youth-identification', 'local');

        if ($attachedIdPath === false) {
            throw new RuntimeException('The identification file could not be stored.');
        }

        try {
            return DB::transaction(function () use ($email, $attributes, $attachedIdPath): YouthValidation {
                $validation = new YouthValidation($attributes);
                $validation->email = strtolower(trim($email));
                $validation->attached_id_path = $attachedIdPath;
                $validation->status = YouthProfileStatus::Pending->value;
                $validation->save();

                return $validation;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($attachedIdPath);

            throw $exception;
        }
    }
}
