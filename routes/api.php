<?php

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/api/auth.php';
require __DIR__.'/api/admin.php';
require __DIR__.'/api/sk-admin.php';
require __DIR__.'/api/youth.php';
require __DIR__.'/api/event.php';
require __DIR__.'/api/sports.php';
require __DIR__.'/api/announcement.php';
require __DIR__.'/api/facility.php';

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
]));

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/{id}/delete', [NotificationController::class, 'destroy']);
});
