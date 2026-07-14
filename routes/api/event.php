<?php

use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/my-events', [EventController::class, 'myEvents'])->name('events.my');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::post('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.update-status');
    Route::post('/events/{event}/delete', [EventController::class, 'destroy'])->name('events.destroy');
    Route::post('/events/{event}/join', [EventController::class, 'join'])->name('events.join');
    Route::get('/events/{event}/participants', [EventController::class, 'participants'])->name('events.participants');
    Route::post('/events/{event}/participants/{user}/attend', [EventController::class, 'markAttendance'])->name('events.participants.attend');
    Route::get('/events/{event}/attendance-logs', [EventController::class, 'attendanceLogs'])->name('events.attendance-logs');
});
