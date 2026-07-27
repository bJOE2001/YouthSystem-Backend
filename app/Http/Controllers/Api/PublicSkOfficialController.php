<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SkAdmin\SkOfficialResource;
use App\Models\SkOfficial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSkOfficialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SkOfficial::query();

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', $search)
                    ->orWhere('barangay', 'LIKE', $search)
                    ->orWhere('position', 'LIKE', $search)
                    ->orWhere('committee', 'LIKE', $search);
            });
        }

        if ($request->filled('barangay')) {
            $query->where('barangay', $request->barangay);
        }

        $query->orderBy('barangay', 'asc')->orderBy('name', 'asc');

        $perPage = (int) $request->input('per_page', 1000);

        $officials = $query->paginate($perPage);

        return SkOfficialResource::collection($officials)->response();
    }
}
