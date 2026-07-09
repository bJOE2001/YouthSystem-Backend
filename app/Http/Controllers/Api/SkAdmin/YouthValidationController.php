<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Actions\SkAdmin\YouthValidation\ApproveYouthRegistrationAction;
use App\Actions\SkAdmin\YouthValidation\DisapproveYouthRegistrationAction;
use App\Actions\SkAdmin\YouthValidation\GetPendingYouthRegistrationsAction;
use App\Actions\SkAdmin\YouthValidation\GetYouthRegistrationDetailsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\SkAdmin\YouthValidationDetailsResource;
use App\Http\Resources\SkAdmin\YouthValidationListResource;
use App\Models\YouthProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class YouthValidationController extends Controller
{
    public function index(Request $request, GetPendingYouthRegistrationsAction $action): JsonResponse
    {
        $records = $action->execute($request->all());

        return YouthValidationListResource::collection($records)->response();
    }

    public function show(YouthProfile $youthProfile, GetYouthRegistrationDetailsAction $action): JsonResponse
    {
        $details = $action->execute($youthProfile);

        return response()->json(YouthValidationDetailsResource::make($details));
    }

    public function approve(YouthProfile $youthProfile, ApproveYouthRegistrationAction $action): JsonResponse
    {
        $youthProfile = $action->execute($youthProfile);

        return response()->json([
            'success' => true,
            'message' => 'Youth registration approved successfully.',
            'data' => YouthValidationDetailsResource::make($youthProfile),
        ]);
    }

    public function disapprove(YouthProfile $youthProfile, DisapproveYouthRegistrationAction $action): JsonResponse
    {
        $youthProfile = $action->execute($youthProfile);

        return response()->json([
            'success' => true,
            'message' => 'Youth registration disapproved successfully.',
            'data' => YouthValidationDetailsResource::make($youthProfile),
        ]);
    }
}
