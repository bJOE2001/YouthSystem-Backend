<?php

use App\Http\Controllers\Api\AnnouncementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::post('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::post('/announcements/{announcement}/delete', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});
