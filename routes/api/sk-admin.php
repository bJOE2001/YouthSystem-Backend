<?php

use App\Http\Controllers\Api\SkAdmin\DashboardController;
use App\Http\Controllers\Api\SkAdmin\ResidentYouthController;
use App\Http\Controllers\Api\SkAdmin\SkOfficialController;
use App\Http\Controllers\Api\SkAdmin\YouthValidationController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'active',
    'role:sk_admin',
])
    ->prefix('sk')
    ->name('sk-admin.')
    ->group(function () {

        /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

        Route::get('/dashboard', [DashboardController::class, '__invoke']);

        /*
    |--------------------------------------------------------------------------
    | Resident Youth Records
    |--------------------------------------------------------------------------
    */

        Route::prefix('resident-youth')->name('resident-youth.')->group(function () {
            Route::get('/', [ResidentYouthController::class, 'index'])->name('index');
            Route::get('/{youthProfile}', [ResidentYouthController::class, 'show'])->name('show');
            Route::post('/', [ResidentYouthController::class, 'store'])->name('store');
            Route::post('/{youthProfile}', [ResidentYouthController::class, 'update'])->name('update');
            Route::post('/{youthProfile}/delete', [ResidentYouthController::class, 'destroy'])->name('destroy');
        });

        /*
    |--------------------------------------------------------------------------
    | Youth Validation (Registration)
    |--------------------------------------------------------------------------
    */

        Route::prefix('youth-registration')->name('youth-registration.')->group(function () {
            Route::get('/', [YouthValidationController::class, 'index'])->name('index');
            Route::get('/{youthProfile}', [YouthValidationController::class, 'show'])->name('show');
            Route::post('/{youthProfile}/approve', [YouthValidationController::class, 'approve'])->name('approve');
            Route::post('/{youthProfile}/disapprove', [YouthValidationController::class, 'disapprove'])->name('disapprove');
        });
        /*
    |--------------------------------------------------------------------------
    | SK Officials
    |--------------------------------------------------------------------------
    */

        Route::prefix('sk-officials')->name('sk-officials.')->group(function () {
            Route::get('/', [SkOfficialController::class, 'index'])->name('index');
            Route::get('/{skOfficial}', [SkOfficialController::class, 'show'])->name('show');
            Route::post('/', [SkOfficialController::class, 'store'])->name('store');
            Route::post('/{skOfficial}/delete', [SkOfficialController::class, 'destroy'])->name('destroy');
        });

    });
