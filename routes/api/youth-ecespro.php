<?php

use App\Http\Controllers\Api\Youth\YouthEcesproController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'active',
    'role:youth',
])
    ->prefix('youth/ecespro')
    ->name('youth-ecespro.')
    ->group(function () {
        Route::get('/settings', [YouthEcesproController::class, 'getSettings']);
        Route::get('/active-program', [YouthEcesproController::class, 'activeProgram']);
        Route::get('/my-application', [YouthEcesproController::class, 'myApplication']);
        Route::post('/apply', [YouthEcesproController::class, 'apply']);
        Route::get('/my-requirements', [YouthEcesproController::class, 'myRequirements']);
        Route::post('/submit-requirements', [YouthEcesproController::class, 'submitRequirements']);
    });
