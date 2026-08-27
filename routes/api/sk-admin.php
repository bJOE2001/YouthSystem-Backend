<?php

use App\Http\Controllers\Api\SkAdmin\DashboardController;
use App\Http\Controllers\Api\SkAdmin\ProfileController;
use App\Http\Controllers\Api\SkAdmin\PurokLibraryController;
use App\Http\Controllers\Api\SkAdmin\ResidentYouthController;
use App\Http\Controllers\Api\SkAdmin\SkOfficialController;
use App\Http\Controllers\Api\SkAdmin\SkSportsProgramController;
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
        | Dashboard & Profile
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [DashboardController::class, '__invoke']);

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/profile/create', [ProfileController::class, 'store'])->name('profile.create');
        Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/picture', [ProfileController::class, 'uploadPicture'])->name('profile.picture');
        Route::post('/profile/picture/remove', [ProfileController::class, 'removePicture'])->name('profile.picture.remove');

        /*
    |--------------------------------------------------------------------------
    | Resident Youth Records
    |--------------------------------------------------------------------------
    */

        Route::prefix('resident-youth')->name('resident-youth.')->group(function () {
            Route::get('/', [ResidentYouthController::class, 'index'])->name('index');
            Route::get('/{youthProfile}', [ResidentYouthController::class, 'show'])->name('show');
            Route::get('/{youthProfile}/bookings', [ResidentYouthController::class, 'bookings'])->name('bookings');
            Route::get('/{youthProfile}/events', [ResidentYouthController::class, 'events'])->name('events');
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

        /*
        |--------------------------------------------------------------------------
        | Sports Programs Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('sports-programs')->name('sports-programs.')->group(function () {
            Route::get('/', [SkSportsProgramController::class, 'index'])->name('index');
            Route::post('/', [SkSportsProgramController::class, 'store'])->name('store');
            Route::get('/{sportsProgram}', [SkSportsProgramController::class, 'show'])->name('show');
            Route::post('/{sportsProgram}', [SkSportsProgramController::class, 'update'])->name('update');
            Route::post('/{sportsProgram}/status', [SkSportsProgramController::class, 'updateStatus'])->name('update-status');
            Route::post('/{sportsProgram}/delete', [SkSportsProgramController::class, 'destroy'])->name('destroy');
            Route::get('/{sportsProgram}/participants-by-barangay', [SkSportsProgramController::class, 'participantsByBarangay'])->name('participants-by-barangay');
        });

        /*
        |--------------------------------------------------------------------------
        | Purok Library Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('purok-library')->name('purok-library.')->group(function () {
            Route::get('/', [PurokLibraryController::class, 'index'])->name('index');
            Route::post('/', [PurokLibraryController::class, 'store'])->name('store');
            Route::get('/{purok}', [PurokLibraryController::class, 'show'])->name('show');
            Route::post('/{purok}', [PurokLibraryController::class, 'update'])->name('update');
            Route::post('/{purok}/delete', [PurokLibraryController::class, 'destroy'])->name('destroy');
        });

    });
