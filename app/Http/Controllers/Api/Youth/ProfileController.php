<?php

namespace App\Http\Controllers\Api\Youth;

use App\Actions\YouthProfile\CreateYouthProfileAction;
use App\Actions\YouthProfile\UpdateYouthProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\YouthProfile\StoreYouthProfileRequest;
use App\Http\Requests\YouthProfile\UpdateYouthProfileRequest;
use App\Http\Resources\YouthProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse|YouthProfileResource
    {
        $user = $request->user();
        $profile = $user->youthProfile;

        if (! $profile) {
            return response()->json(['data' => null]);
        }

        return new YouthProfileResource($profile);
    }

    public function store(StoreYouthProfileRequest $request, CreateYouthProfileAction $createAction): JsonResponse|YouthProfileResource
    {
        $user = $request->user();

        if ($user->youthProfile) {
            return response()->json(['message' => 'Profile already exists.'], 422);
        }

        $profile = $createAction->execute(
            $user,
            $request->safe()->except(['attached_id']),
            $request->file('attached_id')
        );

        return new YouthProfileResource($profile);
    }

    public function update(UpdateYouthProfileRequest $request, UpdateYouthProfileAction $updateAction): JsonResponse|YouthProfileResource
    {
        $user = $request->user();
        $profile = $user->youthProfile;

        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $updatedProfile = $updateAction->execute(
            $profile,
            $request->safe()->except(['attached_id']),
            $request->file('attached_id')
        );

        return new YouthProfileResource($updatedProfile);
    }
}
