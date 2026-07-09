<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

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

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');

});
