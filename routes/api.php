<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/api/auth.php';
require __DIR__.'/api/admin.php';
require __DIR__.'/api/sk-admin.php';
require __DIR__.'/api/youth.php';
require __DIR__.'/api/event.php';

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
]));
