<?php

use App\Http\Controllers\Api\YouthProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'active',
    'role:youth',
])
    ->prefix('youth')
    ->name('youth.')
    ->group(function () {

        /*
    |--------------------------------------------------------------------------
    | Youth Profile
    |--------------------------------------------------------------------------
    */

        Route::get('/profile', [YouthProfileController::class, 'show'])
            ->name('profile.show');

        Route::post('/profile/create', [YouthProfileController::class, 'store'])
            ->name('profile.create');

        Route::post('/profile/update', [YouthProfileController::class, 'update'])
            ->name('profile.update');

    });
