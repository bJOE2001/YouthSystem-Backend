<?php

namespace App\Actions\SkAdmin\YouthValidation;

use App\Enums\YouthProfileStatus;
use App\Models\YouthValidation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DisapproveYouthRegistrationAction
{
    public function execute(YouthValidation $youthValidation, ?string $rejectionReason = null): YouthValidation
    {
        return DB::transaction(function () use ($youthValidation, $rejectionReason): YouthValidation {
            $validation = YouthValidation::query()
                ->whereKey($youthValidation->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $validation->status !== YouthProfileStatus::Pending,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'This youth registration has already been processed.'
            );

            $trimmedReason = $rejectionReason !== null ? trim($rejectionReason) : null;

            $validation->update([
                'status' => YouthProfileStatus::Rejected->value,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => $trimmedReason === '' ? null : $trimmedReason,
            ]);

            return $validation->refresh();
        });
    }
}
