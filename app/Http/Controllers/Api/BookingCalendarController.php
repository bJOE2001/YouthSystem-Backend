<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingCalendarController extends Controller
{
    public function index(Request $request)
    {
        // Only fetch approved bookings for the calendar
        $bookings = BookingRequest::with(['user', 'facility'])
            ->where('status', 'Approved')
            ->get();

        $events = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'title' => $booking->user->name ?? 'Booking',
                'start' => $booking->date.'T'.$booking->start_time,
                'end' => $booking->date.'T'.$booking->end_time,
                'extendedProps' => [
                    'requestedBy' => $booking->user->name ?? 'Unknown',
                    'email' => $booking->user->email ?? 'Unknown',
                    'facility' => $booking->facility->name ?? 'Unknown',
                    'location' => $booking->facility->location ?? 'Unknown',
                    'schedule' => Carbon::parse($booking->date)->format('Y-m-d').' '.Carbon::parse($booking->start_time)->format('h:i A').' - '.Carbon::parse($booking->end_time)->format('h:i A'),
                    'status' => $booking->status,
                ],
            ];
        });

        return response()->json(['data' => $events]);
    }
}
