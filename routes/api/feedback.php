<?php

use App\Http\Controllers\Api\FeedbackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Youth & SK Admin Routes
    Route::post('/feedbacks', [FeedbackController::class, 'store'])->name('feedbacks.store');
    Route::get('/my-feedbacks', [FeedbackController::class, 'myFeedbacks'])->name('feedbacks.my');
    Route::post('/events/{event}/feedbacks', [FeedbackController::class, 'storeEventFeedback'])->name('events.feedbacks.store');
    Route::get('/events/{event}/feedbacks', [FeedbackController::class, 'eventFeedbacks'])->name('events.feedbacks.index');

    // Admin Routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/feedbacks', [FeedbackController::class, 'index'])->name('admin.feedbacks.index');
    });
});
