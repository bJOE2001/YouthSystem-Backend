<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Actions\SkAdmin\YouthValidation\ApproveYouthRegistrationAction;
use App\Actions\SkAdmin\YouthValidation\DisapproveYouthRegistrationAction;
use App\Actions\SkAdmin\YouthValidation\GetPendingYouthRegistrationsAction;
use App\Actions\SkAdmin\YouthValidation\GetYouthRegistrationDetailsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\SkAdmin\YouthValidationDetailsResource;
use App\Http\Resources\SkAdmin\YouthValidationListResource;
use App\Models\YouthValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class YouthValidationController extends Controller
{
    public function index(Request $request, GetPendingYouthRegistrationsAction $action): JsonResponse
    {
        $records = $action->execute($request->all());

        return YouthValidationListResource::collection($records)->response();
    }

    public function show(YouthValidation $youthValidation, GetYouthRegistrationDetailsAction $action): JsonResponse
    {
        $details = $action->execute($youthValidation);

        return response()->json(YouthValidationDetailsResource::make($details));
    }

    public function attachedId(YouthValidation $youthValidation): BinaryFileResponse
    {
        $path = $youthValidation->attached_id_path;
        abort_if(empty($path), Response::HTTP_NOT_FOUND, 'Identification document not found.');

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return response()->file(Storage::disk($disk)->path($path));
            }
        }

        abort(Response::HTTP_NOT_FOUND, 'Identification document not found.');
    }

    public function approve(YouthValidation $youthValidation, ApproveYouthRegistrationAction $action): JsonResponse
    {
        $youthValidation = $action->execute($youthValidation);

        return response()->json([
            'success' => true,
            'message' => 'Youth registration approved successfully.',
            'data' => YouthValidationDetailsResource::make($youthValidation),
        ]);
    }

    public function disapprove(
        Request $request,
        YouthValidation $youthValidation,
        DisapproveYouthRegistrationAction $action
    ): JsonResponse {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $youthValidation = $action->execute(
            $youthValidation,
            $validated['rejection_reason'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Youth registration disapproved successfully.',
            'data' => YouthValidationDetailsResource::make($youthValidation),
        ]);
    }
}
