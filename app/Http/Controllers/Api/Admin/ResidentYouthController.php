<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\SkAdmin\ResidentYouth\CreateResidentYouthRecordAction;
use App\Actions\SkAdmin\ResidentYouth\DeleteResidentYouthRecordAction;
use App\Actions\SkAdmin\ResidentYouth\GetResidentYouthDetailsAction;
use App\Actions\SkAdmin\ResidentYouth\GetResidentYouthRecordsAction;
use App\Actions\SkAdmin\ResidentYouth\UpdateResidentYouthRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SkAdmin\StoreResidentYouthRequest;
use App\Http\Requests\SkAdmin\UpdateResidentYouthRequest;
use App\Http\Resources\BookingRequestResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\SkAdmin\ResidentYouthDetailsResource;
use App\Http\Resources\SkAdmin\ResidentYouthListResource;
use App\Models\BookingRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\YouthProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ResidentYouthController extends Controller
{
    public function index(Request $request, GetResidentYouthRecordsAction $action): JsonResponse
    {
        $records = $action->execute($request->all());

        return ResidentYouthListResource::collection($records)->response();
    }

    public function show(YouthProfile $youthProfile, GetResidentYouthDetailsAction $action): JsonResponse
    {
        $details = $action->execute($youthProfile);

        return response()->json(ResidentYouthDetailsResource::make($details));
    }

    public function store(StoreResidentYouthRequest $request, CreateResidentYouthRecordAction $action): JsonResponse
    {
        Log::info('StoreResidentYouthRequest Data:', $request->all());
        Log::info('StoreResidentYouthRequest Files:', $request->allFiles());

        $youthProfile = $action->execute(
            $request->validated(),
            $request->file('attachedId')
        );

        return response()->json([
            'success' => true,
            'message' => 'Resident youth record created successfully.',
            'data' => ResidentYouthDetailsResource::make($youthProfile),
        ], Response::HTTP_CREATED);
    }

    public function update(
        UpdateResidentYouthRequest $request,
        YouthProfile $youthProfile,
        UpdateResidentYouthRecordAction $action
    ): JsonResponse {
        $updatedProfile = $action->execute(
            $youthProfile,
            $request->validated(),
            $request->file('attachedId')
        );

        return response()->json([
            'success' => true,
            'message' => 'Resident youth record updated successfully.',
            'data' => ResidentYouthDetailsResource::make($updatedProfile),
        ]);
    }

    public function destroy(YouthProfile $youthProfile, DeleteResidentYouthRecordAction $action): JsonResponse
    {
        $action->execute($youthProfile);

        return response()->json([
            'success' => true,
            'message' => 'Resident youth record deleted successfully.',
            'data' => [],
        ]);
    }

    public function changeEmail(Request $request, YouthProfile $youthProfile): JsonResponse
    {
        $user = $youthProfile->user;
        abort_if(! $user, Response::HTTP_NOT_FOUND, 'User account associated with this profile was not found.');

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::notIn([$user->email]),
            ],
        ], [
            'email.not_in' => 'The new email must be different from the current email address.',
        ]);

        $newEmail = $validated['email'];

        $user->email = $newEmail;
        $user->email_verified_at = now();
        $user->save();

        if ($user->skOfficial) {
            $user->skOfficial->email = $newEmail;
            $user->skOfficial->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Resident youth email updated successfully.',
            'data' => ResidentYouthDetailsResource::make($youthProfile->fresh()->loadMissing('user')),
        ]);
    }

    public function assignOrganization(Request $request, YouthProfile $youthProfile): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => 'nullable|integer|exists:organizations,id',
        ]);

        $youthProfile->organization_id = $validated['organization_id'] ?? null;
        $youthProfile->save();

        return response()->json([
            'success' => true,
            'message' => 'Youth organization updated successfully.',
            'data' => ResidentYouthDetailsResource::make($youthProfile->fresh()->loadMissing(['user', 'organization'])),
        ]);
    }

    public function toggleSinag(YouthProfile $youthProfile): JsonResponse
    {
        $sinagOrg = Organization::where('name', 'SINAG')->first();
        if ($youthProfile->organization_id === $sinagOrg?->id) {
            $youthProfile->organization_id = null;
        } else {
            $youthProfile->organization_id = $sinagOrg?->id;
        }

        $youthProfile->save();

        return response()->json([
            'success' => true,
            'message' => 'Organization status updated successfully.',
            'data' => ResidentYouthDetailsResource::make($youthProfile->fresh()->loadMissing(['user', 'organization'])),
        ]);
    }

    public function bookings(YouthProfile $youthProfile): JsonResponse
    {
        $bookings = BookingRequest::with(['facility'])
            ->where('user_id', $youthProfile->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(BookingRequestResource::collection($bookings));
    }

    public function events(YouthProfile $youthProfile): JsonResponse
    {
        $events = Event::whereHas('participants', function ($q) use ($youthProfile) {
            $q->where('user_id', $youthProfile->user_id);
        })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(EventResource::collection($events));
    }
}
