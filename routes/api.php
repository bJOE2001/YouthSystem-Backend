<?php

use App\Http\Controllers\Api\Admin\SystemSettingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PublicBarangayController;
use App\Http\Controllers\Api\PublicLydcMemberController;
use App\Http\Controllers\Api\PublicSkOfficialController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/api/auth.php';
require __DIR__.'/api/admin.php';
require __DIR__.'/api/sk-admin.php';
require __DIR__.'/api/youth.php';
require __DIR__.'/api/youth-ecespro.php';
require __DIR__.'/api/sk-ecespro.php';
require __DIR__.'/api/event.php';
require __DIR__.'/api/sports.php';
require __DIR__.'/api/announcement.php';
require __DIR__.'/api/facility.php';
require __DIR__.'/api/feedback.php';
require __DIR__.'/api/scanner.php';

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
]));

Route::get('/public/sk-officials', [PublicSkOfficialController::class, 'index'])->name('public.sk-officials.index');
Route::get('/sk-officials', [PublicSkOfficialController::class, 'index'])->name('sk-officials.index');

Route::get('/public/lydc-members', [PublicLydcMemberController::class, 'index'])->name('public.lydc-members.index');
Route::get('/lydc-members', [PublicLydcMemberController::class, 'index'])->name('lydc-members.index');

Route::get('/public/barangays', [PublicBarangayController::class, 'index'])->name('public.barangays.index');
Route::get('/barangays', [PublicBarangayController::class, 'index'])->name('barangays.index');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/{id}/delete', [NotificationController::class, 'destroy']);
});

Route::get('/system-settings/landing-hero', [SystemSettingController::class, 'getLandingHero']);

Route::get('/system-settings/auth-hero', [SystemSettingController::class, 'getAuthHero']);

Route::get('/system-settings/contact', [SystemSettingController::class, 'getContactSettings']);

Route::get('/system-settings/email-layout', [SystemSettingController::class, 'getEmailLayout']);
