<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ResidentYouthController;
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
            Route::get('/', [ResidentYouthController::class, 'index'])->name('index');
            Route::get('/{youthProfile}', [ResidentYouthController::class, 'show'])->name('show');
            Route::get('/{youthProfile}/bookings', [ResidentYouthController::class, 'bookings'])->name('bookings');
            Route::get('/{youthProfile}/events', [ResidentYouthController::class, 'events'])->name('events');
            Route::post('/', [ResidentYouthController::class, 'store'])->name('store');
            Route::post('/{youthProfile}', [ResidentYouthController::class, 'update'])->name('update');
            Route::post('/{youthProfile}/delete', [ResidentYouthController::class, 'destroy'])->name('destroy');
            Route::post('/{youthProfile}/toggle-sinag', [ResidentYouthController::class, 'toggleSinag'])->name('toggle-sinag');
        });

    });
