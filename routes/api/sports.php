<?php

use App\Http\Controllers\Api\SportsProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/sports', [SportsProgramController::class, 'index'])->name('sports.index');
    Route::post('/sports', [SportsProgramController::class, 'store'])->name('sports.store');
    Route::get('/sports/{sportsProgram}', [SportsProgramController::class, 'show'])->name('sports.show');
    Route::post('/sports/{sportsProgram}', [SportsProgramController::class, 'update'])->name('sports.update');
    Route::post('/sports/{sportsProgram}/status', [SportsProgramController::class, 'updateStatus'])->name('sports.update-status');
    Route::post('/sports/{sportsProgram}/delete', [SportsProgramController::class, 'destroy'])->name('sports.destroy');
    Route::get('/sports/{sportsProgram}/participants-by-barangay', [SportsProgramController::class, 'participantsByBarangay'])->name('sports.participants-by-barangay');
});
