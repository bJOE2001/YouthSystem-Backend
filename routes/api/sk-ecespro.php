<?php

use App\Http\Controllers\Api\SkAdmin\SkEcesproController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'active',
    'role:sk_admin',
])
    ->prefix('sk/ecespro')
    ->name('sk-ecespro.')
    ->group(function () {
        Route::get('/settings', [SkEcesproController::class, 'getSettings']);
        Route::get('/active-program', [SkEcesproController::class, 'activeProgram']);
        Route::get('/my-application', [SkEcesproController::class, 'myApplication']);
        Route::post('/apply', [SkEcesproController::class, 'apply']);
        Route::get('/my-requirements', [SkEcesproController::class, 'myRequirements']);
        Route::get('/compliance-schedules', [SkEcesproController::class, 'complianceSchedules']);
        Route::post('/submit-requirements', [SkEcesproController::class, 'submitRequirements']);
        Route::post('/requirements-history/{index}', [SkEcesproController::class, 'deleteRequirement']);
        Route::post('/reupload-application-document', [SkEcesproController::class, 'reuploadApplicationDocument']);
    });
