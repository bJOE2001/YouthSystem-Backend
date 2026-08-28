<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purok;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicPurokController extends Controller
{
    /**
     * Display a listing of puroks, optionally filtered by barangay.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->filled('barangay')) {
            return response()->json([
                'data' => [],
            ]);
        }

        $query = Purok::query()
            ->where('barangay', $request->input('barangay'));

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->input('search').'%');
        }

        $puroks = $query->orderBy('name', 'asc')
            ->get(['id', 'name', 'barangay']);

        return response()->json([
            'data' => $puroks,
        ]);
    }
}
