<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Actions\SkAdmin\Dashboard\GetDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\DashboardResource;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(GetDashboardAction $action): JsonResponse
    {
        $data = $action->handle();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard loaded successfully.',
            'data' => new DashboardResource($data),
        ]);
    }
}
