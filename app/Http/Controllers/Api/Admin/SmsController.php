<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SmsController extends Controller
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Get paginated SMS logs with filtering and search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SmsLog::with('user:id,name,email')->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->query('event_type'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }

    /**
     * Send a test SMS to a specified recipient.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:500'],
        ]);

        $result = $this->smsService->send(
            $validated['recipient'],
            $validated['message'],
            $request->user(),
            'test_sms'
        );

        $statusCode = $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        return response()->json([
            'status' => $result['success'] ? 'success' : 'failed',
            'message' => $result['message'],
            'data' => $result['log'] ?? null,
        ], $statusCode);
    }

    /**
     * Diagnostic endpoint to check SMS Gateway connectivity.
     */
    public function diagnostics(): JsonResponse
    {
        $connection = $this->smsService->checkConnection();

        return response()->json([
            'status' => 'success',
            'data' => [
                'enabled' => (bool) config('sms.enabled', true),
                'gateway_url' => config('sms.gateway_url'),
                'timeout' => (int) config('sms.timeout', 10),
                'connectivity' => $connection,
            ],
        ]);
    }
}
