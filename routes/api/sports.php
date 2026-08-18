<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\SportsProgramController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/sports', [SportsProgramController::class, 'index'])->name('sports.index');
Route::get('/sports/{sportsProgram}', [SportsProgramController::class, 'show'])->name('sports.show');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Accessible by youth and sk_admin
    Route::middleware('role:youth,sk_admin')->group(function () {
        Route::post('/sports/{sportsProgram}/join', [SportsProgramController::class, 'join'])->name('sports.join');
        Route::get('/sports/{sportsProgram}/certificate', [EventController::class, 'downloadCertificate'])->name('sports.certificate');
        Route::get('/sports/{sportsProgram}/certificates', [EventController::class, 'downloadCertificate'])->name('sports.certificates');
    });

    // Accessible by admin and sk_admin
    Route::middleware('role:admin,sk_admin')->group(function () {
        Route::post('/sports', [SportsProgramController::class, 'store'])->name('sports.store');
        Route::post('/sports/{sportsProgram}', [SportsProgramController::class, 'update'])->name('sports.update');
        Route::post('/sports/{sportsProgram}/status', [SportsProgramController::class, 'updateStatus'])->name('sports.update-status');
        Route::post('/sports/{sportsProgram}/delete', [SportsProgramController::class, 'destroy'])->name('sports.destroy');
        Route::get('/sports/{sportsProgram}/participants-by-barangay', [SportsProgramController::class, 'participantsByBarangay'])->name('sports.participants-by-barangay');
        Route::post('/sports/{sportsProgram}/certificate', [EventController::class, 'uploadCertificate'])->name('sports.certificate.upload');
        Route::post('/sports/{sportsProgram}/certificates', [EventController::class, 'uploadCertificate'])->name('sports.certificates.upload');
        Route::get('/sports/{sportsProgram}/certificate-preview', [EventController::class, 'certificatePreview'])->name('sports.certificate.preview');
        Route::post('/sports/{sportsProgram}/certificate-preview', [EventController::class, 'certificatePreview'])->name('sports.certificate.preview.post');
        Route::post('/sports/{sportsProgram}/send-certificates', [EventController::class, 'sendCertificates'])->name('sports.certificates.send');
        Route::post('/sports/{sportsProgram}/participants/{user}/send-certificate', [EventController::class, 'sendParticipantCertificate'])->name('sports.participants.send-certificate');
    });
});
