<?php

use App\Http\Controllers\Api\AnnouncementController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('/announcements/read-all', [AnnouncementController::class, 'markAllAsRead'])->name('announcements.read-all');
    Route::post('/announcements/{announcement}/read', [AnnouncementController::class, 'markAsRead'])->name('announcements.read');

    // Accessible by admin, sub_admin, and sk_admin
    Route::middleware(['role:admin,sub_admin,sk_admin', 'module:announcements'])->group(function () {
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::post('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::post('/announcements/{announcement}/delete', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });
});
