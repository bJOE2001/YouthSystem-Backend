<?php

use App\Http\Controllers\Api\YouthProfileController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::get('/user', [AuthController::class, 'user'])
    ->middleware(['auth:sanctum', 'active'])
    ->name('auth.user');

Route::prefix('youth')
    ->middleware(['auth:sanctum', 'active', 'role:youth'])
    ->group(function (): void {
        Route::get('/profile', [YouthProfileController::class, 'show'])
            ->name('youth.profile.show');
        Route::post('/profile', [YouthProfileController::class, 'store'])
            ->name('youth.profile.store');
        Route::match(['put', 'patch'], '/profile', [YouthProfileController::class, 'update'])
            ->name('youth.profile.update');
    });
