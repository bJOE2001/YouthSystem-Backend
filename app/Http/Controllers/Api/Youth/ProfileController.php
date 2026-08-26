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
use Illuminate\Support\Facades\Storage;

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

    /**
     * Upload the authenticated user's profile picture.
     */
    public function uploadPicture(Request $request): JsonResponse|YouthProfileResource
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $user = $request->user();
        $profile = $user->youthProfile;

        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        if ($profile->profile_picture) {
            Storage::disk('public')->delete($profile->profile_picture);
        }

        $path = $request->file('profile_picture')->store('profile-pictures', 'public');

        $profile->update(['profile_picture' => $path]);

        return new YouthProfileResource($profile->fresh());
    }

    /**
     * Remove the authenticated user's profile picture.
     */
    public function removePicture(Request $request): JsonResponse|YouthProfileResource
    {
        $user = $request->user();
        $profile = $user->youthProfile;

        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        if ($profile->profile_picture) {
            Storage::disk('public')->delete($profile->profile_picture);
            $profile->update(['profile_picture' => null]);
        }

        return new YouthProfileResource($profile->fresh());
    }
}
