<?php

use App\Http\Controllers\Api\Admin\BarangayLibraryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\EcesproSettingController;
use App\Http\Controllers\Api\Admin\LydcMemberController;
use App\Http\Controllers\Api\Admin\ResidentYouthController;
use App\Http\Controllers\Api\Admin\SkOfficialController;
use App\Http\Controllers\EcesproApplicationController;
use App\Http\Controllers\EcesproComplianceScheduleController;
use App\Http\Controllers\EcesproContractBatchController;
use App\Http\Controllers\EcesproContractController;
use App\Http\Controllers\EcesproExamBatchController;
use App\Http\Controllers\EcesproExaminationController;
use App\Http\Controllers\EcesproInterviewBatchController;
use App\Http\Controllers\EcesproInterviewController;
use App\Http\Controllers\EcesproProgramController;
use App\Http\Controllers\EcesproScholarController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'active',
    'role:admin,sk_admin',
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
        Route::get('ecespro-programs', [EcesproProgramController::class, 'index']);
        Route::post('ecespro-programs', [EcesproProgramController::class, 'store']);
        Route::get('ecespro-programs/{ecespro_program}', [EcesproProgramController::class, 'show']);
        Route::post('ecespro-programs/{ecespro_program}', [EcesproProgramController::class, 'update']);

        Route::get('ecespro-applications', [EcesproApplicationController::class, 'index']);
        Route::post('ecespro-applications', [EcesproApplicationController::class, 'store']);
        Route::get('ecespro-applications/{ecespro_application}', [EcesproApplicationController::class, 'show']);
        Route::post('ecespro-applications/{ecespro_application}', [EcesproApplicationController::class, 'update']);
        Route::post('ecespro-applications/{ecespro_application}/documents/{document_id}', [EcesproApplicationController::class, 'updateDocumentStatus'])->name('ecespro-applications.documents.update');

        Route::get('ecespro-exam-batches', [EcesproExamBatchController::class, 'index']);
        Route::post('ecespro-exam-batches', [EcesproExamBatchController::class, 'store']);
        Route::get('ecespro-exam-batches/{ecespro_exam_batch}', [EcesproExamBatchController::class, 'show']);
        Route::post('ecespro-exam-batches/{ecespro_exam_batch}/delete', [EcesproExamBatchController::class, 'destroy']);

        Route::post('ecespro-examinations/bulk', [EcesproExaminationController::class, 'bulkUpdate'])->name('ecespro-examinations.bulk');
        Route::get('ecespro-examinations', [EcesproExaminationController::class, 'index']);
        Route::post('ecespro-examinations', [EcesproExaminationController::class, 'store']);
        Route::get('ecespro-examinations/{ecespro_examination}', [EcesproExaminationController::class, 'show']);
        Route::post('ecespro-examinations/{ecespro_examination}', [EcesproExaminationController::class, 'update']);
        Route::post('ecespro-examinations/{ecespro_examination}/remove', [EcesproExaminationController::class, 'destroy']);

        Route::get('ecespro-interview-batches', [EcesproInterviewBatchController::class, 'index']);
        Route::post('ecespro-interview-batches', [EcesproInterviewBatchController::class, 'store']);
        Route::get('ecespro-interview-batches/{ecespro_interview_batch}', [EcesproInterviewBatchController::class, 'show']);
        Route::post('ecespro-interview-batches/{ecespro_interview_batch}/delete', [EcesproInterviewBatchController::class, 'destroy']);

        Route::get('ecespro-interviews', [EcesproInterviewController::class, 'index']);
        Route::post('ecespro-interviews', [EcesproInterviewController::class, 'store']);
        Route::get('ecespro-interviews/{ecespro_interview}', [EcesproInterviewController::class, 'show']);
        Route::post('ecespro-interviews/{ecespro_interview}', [EcesproInterviewController::class, 'update']);
        Route::post('ecespro-interviews/{ecespro_interview}/remove', [EcesproInterviewController::class, 'destroy']);

        Route::post('ecespro-contracts/sign-application/{application}', [EcesproContractController::class, 'signApplication']);
        Route::post('ecespro-contracts/{ecespro_contract}/remove-from-batch', [EcesproContractController::class, 'removeFromBatch']);
        Route::get('ecespro-contracts', [EcesproContractController::class, 'index']);
        Route::post('ecespro-contracts', [EcesproContractController::class, 'store']);
        Route::get('ecespro-contracts/{ecespro_contract}', [EcesproContractController::class, 'show']);
        Route::post('ecespro-contracts/{ecespro_contract}', [EcesproContractController::class, 'update']);

        Route::get('ecespro-contract-signing-batches', [EcesproContractBatchController::class, 'index']);
        Route::post('ecespro-contract-signing-batches', [EcesproContractBatchController::class, 'store']);
        Route::get('ecespro-contract-signing-batches/{ecespro_contract_batch}', [EcesproContractBatchController::class, 'show']);
        Route::post('ecespro-contract-signing-batches/{ecespro_contract_batch}/delete', [EcesproContractBatchController::class, 'destroy']);

        Route::get('ecespro-scholars', [EcesproScholarController::class, 'index']);
        Route::post('ecespro-scholars', [EcesproScholarController::class, 'store']);
        Route::get('ecespro-scholars/{ecespro_scholar}', [EcesproScholarController::class, 'show']);
        Route::post('ecespro-scholars/{ecespro_scholar}/delete', [EcesproScholarController::class, 'destroy']);

        Route::get('ecespro-compliance-validations', [EcesproScholarController::class, 'complianceValidations'])->name('ecespro-compliance-validations.index');
        Route::post('ecespro-compliance-validations/{ecesproScholar}/review', [EcesproScholarController::class, 'reviewCompliance'])->name('ecespro-compliance-validations.review');

        Route::get('ecespro-compliance-schedules', [EcesproComplianceScheduleController::class, 'index']);
        Route::post('ecespro-compliance-schedules', [EcesproComplianceScheduleController::class, 'store']);
        Route::get('ecespro-compliance-schedules/{schedule}', [EcesproComplianceScheduleController::class, 'show']);
        Route::post('ecespro-compliance-schedules/{schedule}', [EcesproComplianceScheduleController::class, 'update']);
        Route::post('ecespro-compliance-schedules/{schedule}/status', [EcesproComplianceScheduleController::class, 'updateStatus'])->name('ecespro-compliance-schedules.update-status');
        Route::get('ecespro-compliance-schedules/{schedule}/submissions', [EcesproComplianceScheduleController::class, 'submissions'])->name('ecespro-compliance-schedules.submissions');
        Route::post('ecespro-compliance-schedules/{schedule}/delete', [EcesproComplianceScheduleController::class, 'destroy']);

        Route::get('ecespro-settings', [EcesproSettingController::class, 'index'])->name('ecespro-settings.index');
        Route::post('ecespro-settings/{key}', [EcesproSettingController::class, 'store'])->name('ecespro-settings.store');

    });
