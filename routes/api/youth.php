<?php

use App\Http\Controllers\Api\Youth\DashboardController;
use App\Http\Controllers\Api\Youth\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'active',
    'role:youth',
])
    ->prefix('youth')
    ->name('youth.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Youth Profile
        |--------------------------------------------------------------------------
        */

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/profile/create', [ProfileController::class, 'store'])->name('profile.create');
        Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    });
