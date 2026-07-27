<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\BarangayLibraryResource;
use App\Models\Barangay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicBarangayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Barangay::query();

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
        }

        $barangays = $query->orderBy('name', 'asc')->get();

        return BarangayLibraryResource::collection($barangays)->response();
    }
}
