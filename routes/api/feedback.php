<?php

use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\FeedbackQuestionnaireController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Youth & SK Admin Routes (Legacy)
    Route::post('/feedbacks', [FeedbackController::class, 'store'])->name('feedbacks.store');
    Route::get('/my-feedbacks', [FeedbackController::class, 'myFeedbacks'])->name('feedbacks.my');
    Route::post('/events/{event}/feedbacks', [FeedbackController::class, 'storeEventFeedback'])->name('events.feedbacks.store');
    Route::get('/events/{event}/feedbacks', [FeedbackController::class, 'eventFeedbacks'])->name('events.feedbacks.index');

    // SK Admin feedback listing (Legacy)
    Route::get('/sk/feedbacks', [FeedbackController::class, 'skFeedbacks'])->name('sk.feedbacks.index');

    // Admin Routes (Legacy)
    Route::middleware(['role:admin,sub_admin', 'module:feedbacks'])->group(function () {
        Route::get('/admin/feedbacks', [FeedbackController::class, 'index'])->name('admin.feedbacks.index');
    });

    // --------------------------------------------------------------------------
    // Dynamic Questionnaire & Evaluation Routes (GET and POST only)
    // --------------------------------------------------------------------------
    Route::get('/feedback-questionnaires', [FeedbackQuestionnaireController::class, 'index'])->name('feedback-questionnaires.index');
    Route::post('/feedback-questionnaires', [FeedbackQuestionnaireController::class, 'store'])->name('feedback-questionnaires.store');
    Route::get('/feedback-questionnaires/responses', [FeedbackQuestionnaireController::class, 'getResponses'])->name('feedback-questionnaires.responses.index');
    Route::get('/feedback-questionnaires/{id}', [FeedbackQuestionnaireController::class, 'show'])->name('feedback-questionnaires.show');
    Route::post('/feedback-questionnaires/{id}', [FeedbackQuestionnaireController::class, 'update'])->name('feedback-questionnaires.update');
    Route::post('/feedback-questionnaires/{id}/delete', [FeedbackQuestionnaireController::class, 'destroy'])->name('feedback-questionnaires.destroy');

    // Activity Assignment
    Route::post('/events/{event}/assign-questionnaire', [FeedbackQuestionnaireController::class, 'assignToEvent'])->name('events.assign-questionnaire');
    Route::get('/events/{event}/questionnaire', [FeedbackQuestionnaireController::class, 'getEventQuestionnaire'])->name('events.questionnaire');
    Route::post('/sports-programs/{sportsProgram}/assign-questionnaire', [FeedbackQuestionnaireController::class, 'assignToSportsProgram'])->name('sports-programs.assign-questionnaire');
    Route::get('/sports-programs/{sportsProgram}/questionnaire', [FeedbackQuestionnaireController::class, 'getSportsProgramQuestionnaire'])->name('sports-programs.questionnaire');

    // Response Submissions & User Pending List
    Route::post('/feedback-questionnaires/{id}/responses', [FeedbackQuestionnaireController::class, 'storeResponse'])->name('feedback-questionnaires.responses.store');
    Route::get('/my-pending-feedback-questionnaires', [FeedbackQuestionnaireController::class, 'myPending'])->name('feedback-questionnaires.my-pending');
    Route::get('/feedback-activities', [FeedbackQuestionnaireController::class, 'getActivitySummaries'])->name('feedback.activities.summary');
});

