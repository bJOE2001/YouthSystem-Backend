<?php

use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // Accessible by youth and sk_admin
    Route::middleware('role:youth,sk_admin')->group(function () {
        Route::get('/my-events', [EventController::class, 'myEvents'])->name('events.my');
        Route::get('/my-activities', [EventController::class, 'myEvents'])->name('activities.my');
        Route::get('/events/{event}/certificate', [EventController::class, 'downloadCertificate'])->name('events.certificate');
        Route::get('/events/{event}/certificates', [EventController::class, 'downloadCertificate'])->name('events.certificates');
        Route::post('/events/{event}/join', [EventController::class, 'join'])->name('events.join');
    });

    // Accessible by admin and sk_admin
    Route::middleware('role:admin,sk_admin')->group(function () {
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::post('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::post('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.update-status');
        Route::post('/events/{event}/delete', [EventController::class, 'destroy'])->name('events.destroy');
        Route::get('/events/{event}/participants', [EventController::class, 'participants'])->name('events.participants');
        Route::post('/events/{event}/participants/{user}/attend', [EventController::class, 'markAttendance'])->name('events.participants.attend');
        Route::post('/events/{event}/certificate', [EventController::class, 'uploadCertificate'])->name('events.certificate.upload');
        Route::post('/events/{event}/certificates', [EventController::class, 'uploadCertificate'])->name('events.certificates.upload');
        Route::get('/events/{event}/certificate-preview', [EventController::class, 'certificatePreview'])->name('events.certificate.preview');
        Route::post('/events/{event}/certificate-preview', [EventController::class, 'certificatePreview'])->name('events.certificate.preview.post');
        Route::get('/events/{event}/attendance-logs', [EventController::class, 'attendanceLogs'])->name('events.attendance-logs');
    });
});
