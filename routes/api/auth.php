<?php

use App\Http\Controllers\Api\Admin\SystemSettingController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('api.login');

Route::post('/register', [AuthController::class, 'register'])
    ->name('api.register');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->middleware('guest')
    ->name('password.update');

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'active',
])->group(function () {

    Route::get('/user', [AuthController::class, 'user'])
        ->name('auth.user');

    Route::get('/user/qr-pass', [AuthController::class, 'qrPass'])
        ->name('auth.qr-pass');

    Route::post('/user/qr-pass/regenerate', [AuthController::class, 'regenerateQrPass'])
        ->name('auth.qr-pass.regenerate');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');

    Route::post('/change-password', [SystemSettingController::class, 'changePassword'])
        ->name('auth.change-password');
});

