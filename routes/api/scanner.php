<?php

use App\Http\Controllers\Api\ScannerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'module:scanner'])->prefix('scanner')->group(function () {
    Route::get('/activities', [ScannerController::class, 'activities'])->name('scanner.activities');
    Route::get('/recent-logs', [ScannerController::class, 'recentLogs'])->name('scanner.recent-logs');
    Route::post('/record-scan', [ScannerController::class, 'recordScan'])->name('scanner.record-scan');
    Route::post('/scan', [ScannerController::class, 'recordScan'])->name('scanner.scan');
});
