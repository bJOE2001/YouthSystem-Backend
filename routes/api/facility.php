<?php

use App\Http\Controllers\Api\BookingCalendarController;
use App\Http\Controllers\Api\BookingRequestController;
use App\Http\Controllers\Api\FacilityController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/facilities', [FacilityController::class, 'index'])->name('facilities.index');
Route::get('/facilities/{facility}', [FacilityController::class, 'show'])->name('facilities.show');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Accessible by all authenticated users
    Route::middleware('role:admin,sk_admin,youth')->group(function () {
        Route::get('/facilities/{facility}/calendar', [FacilityController::class, 'calendar'])->name('facilities.calendar');
    });

    // Accessible by youth and sk_admin
    Route::middleware('role:youth,sk_admin')->group(function () {
        Route::post('/facilities/{facility}/book', [FacilityController::class, 'book'])->name('facilities.book');
        Route::get('/my-bookings', [BookingRequestController::class, 'myBookings'])->name('my-bookings.index');
    });

    // Accessible by admin
    Route::middleware('role:admin')->group(function () {
        // Facility Management
        Route::post('/facilities', [FacilityController::class, 'store'])->name('facilities.store');
        Route::post('/facilities/{facility}', [FacilityController::class, 'update'])->name('facilities.update');
        Route::post('/facilities/{facility}/status', [FacilityController::class, 'status'])->name('facilities.status');
        Route::post('/facilities/{facility}/delete', [FacilityController::class, 'destroy'])->name('facilities.destroy');

        // Booking Requests
        Route::get('/booking-request', [BookingRequestController::class, 'index'])->name('booking-request.index');
        Route::get('/booking-request/{booking_request}', [BookingRequestController::class, 'show'])->name('booking-request.show');
        Route::post('/booking-request/{booking_request}/approve', [BookingRequestController::class, 'approve'])->name('booking-request.approve');
        Route::post('/booking-request/{booking_request}/reject', [BookingRequestController::class, 'reject'])->name('booking-request.reject');
        Route::post('/booking-request/{booking_request}/cancel', [BookingRequestController::class, 'cancel'])->name('booking-request.cancel');

        // Booking Calendar
        Route::get('/booking-calendar', [BookingCalendarController::class, 'index'])->name('booking-calendar.index');
    });
});
