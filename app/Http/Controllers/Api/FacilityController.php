<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use App\Models\FacilityBlackoutDate;
use App\Models\User;
use App\Notifications\NewBookingRequestNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        // 1. Strict Facility Status Guard
        if ($facility->status !== 'Active') {
            throw ValidationException::withMessages([
                'facility' => ["This facility is currently {$facility->status} and cannot be booked."],
            ]);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'purpose' => 'required|string',
        ]);

        $todayDate = now()->toDateString();
        $currentTime = now()->format('H:i');

        // 2. No Past Date / Past Time Bookings
        if ($validated['date'] < $todayDate) {
            throw ValidationException::withMessages([
                'date' => ['You cannot book a facility for a past date.'],
            ]);
        }

        if ($validated['date'] === $todayDate && $validated['start_time'] <= $currentTime) {
            throw ValidationException::withMessages([
                'start_time' => ['You cannot book a time slot that has already passed today.'],
            ]);
        }

        // 3. Maintenance / Holiday Blackout Dates Guard
        $blackout = FacilityBlackoutDate::where('date', $validated['date'])
            ->where(function ($q) use ($facility) {
                $q->whereNull('facility_id')
                    ->orWhere('facility_id', $facility->id);
            })
            ->first();

        if ($blackout) {
            $reasonStr = $blackout->reason ? " ({$blackout->reason})" : '';
            throw ValidationException::withMessages([
                'date' => ["Bookings are unavailable on {$validated['date']} due to scheduled maintenance or holiday{$reasonStr}."],
            ]);
        }

        // 4. Active Booking Limit per User (Max 3 active/pending bookings)
        $activeBookingsCount = $request->user()->bookingRequests()
            ->whereIn('status', ['Pending', 'Approved'])
            ->where('date', '>=', $todayDate)
            ->count();

        if ($activeBookingsCount >= 3) {
            throw ValidationException::withMessages([
                'date' => ['You have reached the maximum limit of 3 active or pending booking requests.'],
            ]);
        }

        // 5. Operating Hours Enforcement
        if ($facility->available_time) {
            $availRange = $this->parseFacilityTimeRange($facility->available_time);

            if ($availRange) {
                [$availStart, $availEnd] = $availRange;

                $availStartFormatted = Carbon::parse($availStart)->format('g:i A');
                $availEndFormatted = Carbon::parse($availEnd)->format('g:i A');

                if ($validated['start_time'] < $availStart || $validated['end_time'] > $availEnd) {
                    throw ValidationException::withMessages([
                        'start_time' => ["The requested booking time must be within the facility's available hours ({$availStartFormatted} - {$availEndFormatted})."],
                    ]);
                }
            }
        }

        // 6. Overlapping Booking Conflict Guard
        $existingBooking = $facility->bookingRequests()
            ->where('date', $validated['date'])
            ->whereIn('status', ['Pending', 'Approved'])
            ->where(function ($q) use ($validated) {
                $q->where(function ($query) use ($validated) {
                    $query->where('start_time', '<', $validated['end_time'])
                        ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->exists();

        if ($existingBooking) {
            throw ValidationException::withMessages([
                'date' => ['The facility is already booked or has a pending request for the selected date and time range.'],
            ]);
        }

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

    private function parseFacilityTimeRange(string $availableTime): ?array
    {
        if (empty($availableTime)) {
            return null;
        }

        $parts = preg_split('/\s*(-|to)\s*/i', trim($availableTime));
        if (count($parts) !== 2) {
            return null;
        }

        try {
            $start = Carbon::parse(trim($parts[0]))->format('H:i');
            $end = Carbon::parse(trim($parts[1]))->format('H:i');

            return [$start, $end];
        } catch (\Throwable $e) {
            return null;
        }
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
