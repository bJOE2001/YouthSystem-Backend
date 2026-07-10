<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\SkOfficialController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'active',
    'role:admin',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::prefix('sk-officials')->name('sk-officials.')->group(function () {
            Route::get('/', [SkOfficialController::class, 'index'])->name('index');
            Route::get('/{skOfficial}', [SkOfficialController::class, 'show'])->name('show');
            Route::post('/', [SkOfficialController::class, 'store'])->name('store');
            Route::post('/{skOfficial}/delete', [SkOfficialController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('resident-youth')->name('resident-youth.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\ResidentYouthController::class, 'index'])->name('index');
            Route::get('/{youthProfile}', [\App\Http\Controllers\Api\Admin\ResidentYouthController::class, 'show'])->name('show');
            Route::post('/', [\App\Http\Controllers\Api\Admin\ResidentYouthController::class, 'store'])->name('store');
            Route::put('/{youthProfile}', [\App\Http\Controllers\Api\Admin\ResidentYouthController::class, 'update'])->name('update');
            Route::post('/{youthProfile}/delete', [\App\Http\Controllers\Api\Admin\ResidentYouthController::class, 'destroy'])->name('destroy');
            Route::post('/{youthProfile}/toggle-sinag', [\App\Http\Controllers\Api\Admin\ResidentYouthController::class, 'toggleSinag'])->name('toggle-sinag');
        });

    });
