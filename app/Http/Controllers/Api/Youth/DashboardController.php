<?php

namespace App\Http\Controllers\Api\Youth;

use App\Actions\Youth\Dashboard\GetYouthDashboardAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index(GetYouthDashboardAction $action): JsonResponse
    {
        $data = $action->handle();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard loaded successfully.',
            'data' => $data,
        ]);
    }
}
