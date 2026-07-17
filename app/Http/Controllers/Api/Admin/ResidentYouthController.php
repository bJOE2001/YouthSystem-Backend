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
use App\Models\YouthProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    public function toggleSinag(YouthProfile $youthProfile): JsonResponse
    {
        $youthProfile->sinag_member = ! $youthProfile->sinag_member;
        $youthProfile->save();

        return response()->json([
            'success' => true,
            'message' => 'Sinag status updated successfully.',
            'data' => ResidentYouthDetailsResource::make($youthProfile),
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
