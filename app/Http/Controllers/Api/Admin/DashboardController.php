<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Dashboard\GetAdminDashboard;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(GetAdminDashboard $dashboard)
    {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard loaded successfully.',
            'data' => $dashboard->handle(),
        ]);
    }
}
