<?php

use App\Http\Controllers\Api\SportsProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Accessible by all
    Route::middleware('role:admin,sk_admin,youth')->group(function () {
        Route::get('/sports', [SportsProgramController::class, 'index'])->name('sports.index');
        Route::get('/sports/{sportsProgram}', [SportsProgramController::class, 'show'])->name('sports.show');
    });

    // Accessible by admin and sk_admin
    Route::middleware('role:admin,sk_admin')->group(function () {
        Route::post('/sports', [SportsProgramController::class, 'store'])->name('sports.store');
        Route::post('/sports/{sportsProgram}', [SportsProgramController::class, 'update'])->name('sports.update');
        Route::post('/sports/{sportsProgram}/status', [SportsProgramController::class, 'updateStatus'])->name('sports.update-status');
        Route::post('/sports/{sportsProgram}/delete', [SportsProgramController::class, 'destroy'])->name('sports.destroy');
        Route::get('/sports/{sportsProgram}/participants-by-barangay', [SportsProgramController::class, 'participantsByBarangay'])->name('sports.participants-by-barangay');
    });
});
