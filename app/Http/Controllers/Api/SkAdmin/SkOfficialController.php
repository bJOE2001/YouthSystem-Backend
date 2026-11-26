<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Actions\SkAdmin\SkOfficials\CreateSkOfficialAction;
use App\Actions\SkAdmin\SkOfficials\DeleteSkOfficialAction;
use App\Actions\SkAdmin\SkOfficials\GetSkOfficialDetailsAction;
use App\Actions\SkAdmin\SkOfficials\GetSkOfficialsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\SkAdmin\SkOfficialResource;
use App\Models\SkOfficial;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SkOfficialController extends Controller
{
    public function index(Request $request, GetSkOfficialsAction $action): JsonResponse
    {
        $user = $request->user();
        $params = $request->all();

        // Scope strictly to SK admin's barangay
        $barangay = $user?->getBarangay();
        if (! $barangay) {
            abort(403, 'No barangay assigned to this SK Admin account.');
        }

        $params['barangay'] = $barangay;

        $officials = $action->execute($params);

        return SkOfficialResource::collection($officials)->response();
    }

    public function eligibleYouths(Request $request): JsonResponse
    {
        abort(Response::HTTP_FORBIDDEN, 'Only administrators can manage SK officials.');
    }

    protected function authorizeBarangayOfficial(User $user, SkOfficial $skOfficial): void
    {
        $skBarangay = $user->getBarangay();
        if (! $skBarangay || strtolower(trim((string) $skOfficial->barangay)) !== strtolower(trim((string) $skBarangay))) {
            abort(403, 'You are not authorized to access SK officials outside your assigned barangay.');
        }
    }

    public function store(Request $request, CreateSkOfficialAction $action): JsonResponse
    {
        abort(Response::HTTP_FORBIDDEN, 'Only administrators can set a youth as an SK official.');
    }

    public function show(Request $request, SkOfficial $skOfficial, GetSkOfficialDetailsAction $action): JsonResponse
    {
        $this->authorizeBarangayOfficial($request->user(), $skOfficial);
        $official = $action->execute($skOfficial->loadMissing('user.youthProfile'));

        return response()->json(SkOfficialResource::make($official));
    }

    public function destroy(Request $request, SkOfficial $skOfficial, DeleteSkOfficialAction $action): JsonResponse
    {
        abort(Response::HTTP_FORBIDDEN, 'Only administrators can remove an SK official.');
    }
}
