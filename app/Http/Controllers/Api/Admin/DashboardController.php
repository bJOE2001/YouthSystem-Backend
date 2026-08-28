<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Dashboard\GetAdminDashboard;
use App\Actions\Dashboard\GetEngagementMetricsAction;
use App\Actions\Dashboard\GetYouthParticipationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function engagementMetrics(Request $request, GetEngagementMetricsAction $action): JsonResponse
    {
        $refresh = $request->boolean('refresh', false);
        $data = $action->execute($refresh);

        return response()->json($data);
    }

    public function youthParticipation(Request $request, GetYouthParticipationAction $action): JsonResponse
    {
        $refresh = $request->boolean('refresh', false);
        $data = $action->execute($refresh);

        return response()->json($data);
    }
}
