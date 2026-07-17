<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingRequestResource;
use App\Models\BookingRequest;
use App\Notifications\BookingApprovedNotification;
use App\Notifications\BookingRejectedNotification;
use Illuminate\Http\Request;

class BookingRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = BookingRequest::with(['user', 'facility']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('facility', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'name' || $sortBy === 'requestedBy') {
            $query->join('users', 'booking_requests.user_id', '=', 'users.id')
                ->orderBy('users.name', $sortOrder)
                ->select('booking_requests.*');
        } elseif ($sortBy === 'facility') {
            $query->join('facilities', 'booking_requests.facility_id', '=', 'facilities.id')
                ->orderBy('facilities.name', $sortOrder)
                ->select('booking_requests.*');
        } elseif ($sortBy === 'dateTime') {
            $query->orderBy('date', $sortOrder)->orderBy('start_time', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->input('per_page', 10);
        $requests = $perPage > 0 ? $query->paginate($perPage) : $query->get();

        return BookingRequestResource::collection($requests);
    }

    public function show(BookingRequest $bookingRequest)
    {
        return new BookingRequestResource($bookingRequest->load(['user', 'facility']));
    }

    public function approve(BookingRequest $bookingRequest)
    {
        $bookingRequest->update(['status' => 'Approved']);
        $bookingRequest->user->notify(new BookingApprovedNotification($bookingRequest));

        return response()->json(['message' => 'Booking request approved']);
    }

    public function reject(BookingRequest $bookingRequest)
    {
        $bookingRequest->update(['status' => 'Declined']);
        $bookingRequest->user->notify(new BookingRejectedNotification($bookingRequest));

        return response()->json(['message' => 'Booking request rejected']);
    }

    public function cancel(BookingRequest $bookingRequest)
    {
        $bookingRequest->update(['status' => 'Cancelled']);

        return response()->json(['message' => 'Booking request cancelled']);
    }

    public function myBookings(Request $request)
    {
        $query = BookingRequest::with(['facility'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('facility', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'facility') {
            $query->join('facilities', 'booking_requests.facility_id', '=', 'facilities.id')
                ->orderBy('facilities.name', $sortOrder)
                ->select('booking_requests.*');
        } elseif ($sortBy === 'dateTime') {
            $query->orderBy('date', $sortOrder)->orderBy('start_time', $sortOrder);
        } else {
            // Default sorting for valid columns, e.g., status or created_at
            // the frontend might send 'name' by default, if it does map it to created_at
            if ($sortBy === 'name') {
                $sortBy = 'created_at';
            }
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->input('per_page', 10);
        $requests = $perPage > 0 ? $query->paginate($perPage) : $query->get();

        return BookingRequestResource::collection($requests);
    }
}
