<?php

use App\Http\Controllers\Api\Admin\BarangayLibraryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\EcesproSettingController;
use App\Http\Controllers\Api\Admin\LydcMemberController;
use App\Http\Controllers\Api\Admin\ResidentYouthController;
use App\Http\Controllers\Api\Admin\SkOfficialController;
use App\Http\Controllers\EcesproApplicationController;
use App\Http\Controllers\EcesproComplianceScheduleController;
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

        // ECESPRO Programs
        Route::get('ecespro-programs', [EcesproProgramController::class, 'index'])->name('ecespro-programs.index');
        Route::post('ecespro-programs', [EcesproProgramController::class, 'store'])->name('ecespro-programs.store');
        Route::get('ecespro-programs/{ecespro_program}', [EcesproProgramController::class, 'show'])->name('ecespro-programs.show');
        Route::post('ecespro-programs/{ecespro_program}', [EcesproProgramController::class, 'update'])->name('ecespro-programs.update');
        Route::post('ecespro-programs/{ecespro_program}/delete', [EcesproProgramController::class, 'destroy'])->name('ecespro-programs.destroy');

        // ECESPRO Applications
        Route::get('ecespro-applications', [EcesproApplicationController::class, 'index'])->name('ecespro-applications.index');
        Route::post('ecespro-applications', [EcesproApplicationController::class, 'store'])->name('ecespro-applications.store');
        Route::get('ecespro-applications/{ecespro_application}', [EcesproApplicationController::class, 'show'])->name('ecespro-applications.show');
        Route::post('ecespro-applications/{ecespro_application}', [EcesproApplicationController::class, 'update'])->name('ecespro-applications.update');
        Route::post('ecespro-applications/{ecespro_application}/delete', [EcesproApplicationController::class, 'destroy'])->name('ecespro-applications.destroy');
        Route::post('ecespro-applications/{ecespro_application}/documents/{document_id}', [EcesproApplicationController::class, 'updateDocumentStatus'])->name('ecespro-applications.documents.update');

        // ECESPRO Exam Batches
        Route::get('ecespro-exam-batches', [EcesproExamBatchController::class, 'index'])->name('ecespro-exam-batches.index');
        Route::post('ecespro-exam-batches', [EcesproExamBatchController::class, 'store'])->name('ecespro-exam-batches.store');
        Route::get('ecespro-exam-batches/{ecespro_exam_batch}', [EcesproExamBatchController::class, 'show'])->name('ecespro-exam-batches.show');
        Route::post('ecespro-exam-batches/{ecespro_exam_batch}', [EcesproExamBatchController::class, 'update'])->name('ecespro-exam-batches.update');
        Route::post('ecespro-exam-batches/{ecespro_exam_batch}/delete', [EcesproExamBatchController::class, 'destroy'])->name('ecespro-exam-batches.destroy');

        // ECESPRO Examinations
        Route::get('ecespro-examinations', [EcesproExaminationController::class, 'index'])->name('ecespro-examinations.index');
        Route::post('ecespro-examinations', [EcesproExaminationController::class, 'store'])->name('ecespro-examinations.store');
        Route::get('ecespro-examinations/{ecespro_examination}', [EcesproExaminationController::class, 'show'])->name('ecespro-examinations.show');
        Route::post('ecespro-examinations/{ecespro_examination}', [EcesproExaminationController::class, 'update'])->name('ecespro-examinations.update');
        Route::post('ecespro-examinations/{ecespro_examination}/delete', [EcesproExaminationController::class, 'destroy'])->name('ecespro-examinations.destroy');
        Route::post('ecespro-examinations/bulk', [EcesproExaminationController::class, 'bulkUpdate'])->name('ecespro-examinations.bulk');

        // ECESPRO Interview Batches
        Route::get('ecespro-interview-batches', [EcesproInterviewBatchController::class, 'index'])->name('ecespro-interview-batches.index');
        Route::post('ecespro-interview-batches', [EcesproInterviewBatchController::class, 'store'])->name('ecespro-interview-batches.store');
        Route::get('ecespro-interview-batches/{ecespro_interview_batch}', [EcesproInterviewBatchController::class, 'show'])->name('ecespro-interview-batches.show');
        Route::post('ecespro-interview-batches/{ecespro_interview_batch}', [EcesproInterviewBatchController::class, 'update'])->name('ecespro-interview-batches.update');
        Route::post('ecespro-interview-batches/{ecespro_interview_batch}/delete', [EcesproInterviewBatchController::class, 'destroy'])->name('ecespro-interview-batches.destroy');

        // ECESPRO Interviews
        Route::get('ecespro-interviews', [EcesproInterviewController::class, 'index'])->name('ecespro-interviews.index');
        Route::post('ecespro-interviews', [EcesproInterviewController::class, 'store'])->name('ecespro-interviews.store');
        Route::get('ecespro-interviews/{ecespro_interview}', [EcesproInterviewController::class, 'show'])->name('ecespro-interviews.show');
        Route::post('ecespro-interviews/{ecespro_interview}', [EcesproInterviewController::class, 'update'])->name('ecespro-interviews.update');
        Route::post('ecespro-interviews/{ecespro_interview}/delete', [EcesproInterviewController::class, 'destroy'])->name('ecespro-interviews.destroy');

        // ECESPRO Contracts
        Route::get('ecespro-contracts', [EcesproContractController::class, 'index'])->name('ecespro-contracts.index');
        Route::post('ecespro-contracts', [EcesproContractController::class, 'store'])->name('ecespro-contracts.store');
        Route::get('ecespro-contracts/{ecespro_contract}', [EcesproContractController::class, 'show'])->name('ecespro-contracts.show');
        Route::post('ecespro-contracts/{ecespro_contract}', [EcesproContractController::class, 'update'])->name('ecespro-contracts.update');
        Route::post('ecespro-contracts/{ecespro_contract}/delete', [EcesproContractController::class, 'destroy'])->name('ecespro-contracts.destroy');
        Route::post('ecespro-contracts/sign-application/{application}', [EcesproContractController::class, 'signApplication']);

        // ECESPRO Scholars
        Route::get('ecespro-scholars', [EcesproScholarController::class, 'index'])->name('ecespro-scholars.index');
        Route::post('ecespro-scholars', [EcesproScholarController::class, 'store'])->name('ecespro-scholars.store');
        Route::get('ecespro-scholars/{ecespro_scholar}', [EcesproScholarController::class, 'show'])->name('ecespro-scholars.show');
        Route::post('ecespro-scholars/{ecespro_scholar}', [EcesproScholarController::class, 'update'])->name('ecespro-scholars.update');
        Route::post('ecespro-scholars/{ecespro_scholar}/delete', [EcesproScholarController::class, 'destroy'])->name('ecespro-scholars.destroy');
        Route::get('ecespro-compliance-validations', [EcesproScholarController::class, 'complianceValidations'])->name('ecespro-compliance-validations.index');
        Route::post('ecespro-compliance-validations/{ecesproScholar}/review', [EcesproScholarController::class, 'reviewCompliance'])->name('ecespro-compliance-validations.review');

        // ECESPRO Compliance Schedules
        Route::get('ecespro-compliance-schedules', [EcesproComplianceScheduleController::class, 'index'])->name('ecespro-compliance-schedules.index');
        Route::post('ecespro-compliance-schedules', [EcesproComplianceScheduleController::class, 'store'])->name('ecespro-compliance-schedules.store');
        Route::get('ecespro-compliance-schedules/{schedule}', [EcesproComplianceScheduleController::class, 'show'])->name('ecespro-compliance-schedules.show');
        Route::post('ecespro-compliance-schedules/{schedule}', [EcesproComplianceScheduleController::class, 'update'])->name('ecespro-compliance-schedules.update');
        Route::post('ecespro-compliance-schedules/{schedule}/delete', [EcesproComplianceScheduleController::class, 'destroy'])->name('ecespro-compliance-schedules.destroy');
        Route::post('ecespro-compliance-schedules/{schedule}/status', [EcesproComplianceScheduleController::class, 'updateStatus'])->name('ecespro-compliance-schedules.update-status');
        Route::get('ecespro-compliance-schedules/{schedule}/submissions', [EcesproComplianceScheduleController::class, 'submissions'])->name('ecespro-compliance-schedules.submissions');

        // ECESPRO Settings
        Route::get('ecespro-settings', [EcesproSettingController::class, 'index'])->name('ecespro-settings.index');
        Route::post('ecespro-settings/{key}', [EcesproSettingController::class, 'store'])->name('ecespro-settings.store');

    });
