<?php

use App\Http\Controllers\Api\ScannerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:admin,sk_admin'])->prefix('scanner')->group(function () {
    Route::get('/activities', [ScannerController::class, 'activities'])->name('scanner.activities');
    Route::post('/record-scan', [ScannerController::class, 'recordScan'])->name('scanner.record-scan');
    Route::post('/scan', [ScannerController::class, 'recordScan'])->name('scanner.scan');
});
