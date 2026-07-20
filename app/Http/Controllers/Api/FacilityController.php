<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use App\Models\User;
use App\Notifications\NewBookingRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        $query = Facility::query();

        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();
            $query->withCount(['bookingRequests as already_booked' => function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->whereIn('status', ['Pending', 'Approved']);
            }]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%");
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 10);
        $facilities = $perPage > 0 ? $query->paginate($perPage) : $query->get();

        return FacilityResource::collection($facilities);
    }

    public function show(Facility $facility)
    {
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();
            $facility->loadCount(['bookingRequests as already_booked' => function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->whereIn('status', ['Pending', 'Approved']);
            }]);
        }

        return new FacilityResource($facility);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'available_time' => 'required|string|max:255',
            'image' => 'nullable|image|max:10240',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('facilities', 'public');
        }

        $facility = Facility::create($validated);

        return new FacilityResource($facility);
    }

    public function update(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'available_time' => 'required|string|max:255',
            'image' => 'nullable|image|max:10240',
        ]);

        if ($request->hasFile('image')) {
            if ($facility->image) {
                Storage::disk('public')->delete($facility->image);
            }
            $validated['image'] = $request->file('image')->store('facilities', 'public');
        }

        $facility->update($validated);

        return new FacilityResource($facility);
    }

    public function status(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Active,Inactive,Under Construction',
        ]);

        $facility->update(['status' => $validated['status']]);

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function destroy(Facility $facility)
    {
        if ($facility->image) {
            Storage::disk('public')->delete($facility->image);
        }
        $facility->delete();

        return response()->json(['message' => 'Facility deleted successfully']);
    }

    public function book(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'purpose' => 'required|string',
        ]);

        $booking = $facility->bookingRequests()->create([
            'user_id' => $request->user()->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'purpose' => $validated['purpose'],
            'status' => 'Pending',
        ]);

        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new NewBookingRequestNotification($booking));

        return response()->json([
            'message' => 'Booking request submitted successfully',
            'data' => $booking,
        ], 201);
    }

    public function calendar(Facility $facility)
    {
        $bookings = $facility->bookingRequests()
            ->with('user')
            ->where('status', 'Approved')
            ->get();

        $events = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'title' => 'Booked',
                'start' => $booking->date.'T'.$booking->start_time,
                'end' => $booking->date.'T'.$booking->end_time,
                'extendedProps' => [
                    'requestedBy' => $booking->user->name ?? 'Unknown',
                    'status' => $booking->status,
                ],
            ];
        });

        return response()->json(['data' => $events]);
    }
}
