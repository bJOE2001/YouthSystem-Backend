<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Actions\YouthProfile\CreateYouthProfileAction;
use App\Actions\YouthProfile\UpdateYouthProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\YouthProfile\StoreYouthProfileRequest;
use App\Http\Requests\YouthProfile\UpdateYouthProfileRequest;
use App\Http\Resources\YouthProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class YouthProfileController extends Controller
{
    public function show(Request $request): YouthProfileResource
    {
        $youthProfile = $request->user()->youthProfile()->with('user')->firstOrFail();

        Gate::authorize('view', $youthProfile);

        return new YouthProfileResource($youthProfile);
    }

    public function store(
        StoreYouthProfileRequest $request,
        CreateYouthProfileAction $createYouthProfile,
    ): JsonResponse {
        $youthProfile = $createYouthProfile->execute(
            $request->user(),
            $request->safe()->except(['attached_id']),
            $request->file('attached_id'),
        );

        return (new YouthProfileResource($youthProfile->load('user')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateYouthProfileRequest $request,
        UpdateYouthProfileAction $updateYouthProfile,
    ): YouthProfileResource {
        $youthProfile = $request->user()->youthProfile()->firstOrFail();

        Gate::authorize('update', $youthProfile);

        $youthProfile = $updateYouthProfile->execute(
            $youthProfile,
            $request->safe()->except(['attached_id']),
            $request->file('attached_id'),
        );

        return new YouthProfileResource($youthProfile->load('user'));
    }
}
