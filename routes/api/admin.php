<?php

use App\Http\Controllers\Api\Admin\BarangayLibraryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\EcesproSettingController;
use App\Http\Controllers\Api\Admin\LydcMemberController;
use App\Http\Controllers\Api\Admin\ResidentYouthController;
use App\Http\Controllers\Api\Admin\SkOfficialController;
use App\Http\Controllers\EcesproApplicationController;
use App\Http\Controllers\EcesproContractController;
use App\Http\Controllers\EcesproExamBatchController;
use App\Http\Controllers\EcesproExaminationController;
use App\Http\Controllers\EcesproInterviewBatchController;
use App\Http\Controllers\EcesproInterviewController;
use App\Http\Controllers\EcesproProgramController;
use App\Http\Controllers\EcesproRequirementController;
use App\Http\Controllers\EcesproScholarController;
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

        Route::prefix('barangay-library')->name('barangay-library.')->group(function () {
            Route::get('/', [BarangayLibraryController::class, 'index'])->name('index');
            Route::get('/{barangay}', [BarangayLibraryController::class, 'show'])->name('show');
            Route::post('/', [BarangayLibraryController::class, 'store'])->name('store');
            Route::post('/{barangay}', [BarangayLibraryController::class, 'update'])->name('update');
            Route::post('/{barangay}/delete', [BarangayLibraryController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('sk-officials')->name('sk-officials.')->group(function () {

            Route::get('/', [SkOfficialController::class, 'index'])->name('index');
            Route::get('/{skOfficial}', [SkOfficialController::class, 'show'])->name('show');
            Route::post('/', [SkOfficialController::class, 'store'])->name('store');
            Route::post('/{skOfficial}/delete', [SkOfficialController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('lydc-members')->name('lydc-members.')->group(function () {
            Route::get('/', [LydcMemberController::class, 'index'])->name('index');
            Route::get('/{lydcMember}', [LydcMemberController::class, 'show'])->name('show');
            Route::post('/', [LydcMemberController::class, 'store'])->name('store');
            Route::post('/{lydcMember}/delete', [LydcMemberController::class, 'destroy'])->name('destroy');
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

        // ECESPRO Routes
        Route::apiResource('ecespro-programs', EcesproProgramController::class);
        Route::apiResource('ecespro-requirements', EcesproRequirementController::class);
        Route::apiResource('ecespro-applications', EcesproApplicationController::class);
        Route::put('ecespro-applications/{ecespro_application}/documents/{document_id}', [EcesproApplicationController::class, 'updateDocumentStatus'])->name('ecespro-applications.documents.update');
        Route::apiResource('ecespro-exam-batches', EcesproExamBatchController::class);
        Route::apiResource('ecespro-examinations', EcesproExaminationController::class);
        Route::apiResource('ecespro-interview-batches', EcesproInterviewBatchController::class);
        Route::apiResource('ecespro-interviews', EcesproInterviewController::class);
        Route::apiResource('ecespro-contracts', EcesproContractController::class);
        Route::apiResource('ecespro-scholars', EcesproScholarController::class);

        Route::get('ecespro-settings', [EcesproSettingController::class, 'index'])->name('ecespro-settings.index');
        Route::post('ecespro-settings/{key}', [EcesproSettingController::class, 'store'])->name('ecespro-settings.store');

    });
