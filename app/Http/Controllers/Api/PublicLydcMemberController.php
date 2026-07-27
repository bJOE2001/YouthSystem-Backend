<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LydcMemberResource;
use App\Models\LydcMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicLydcMemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LydcMember::query();

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', $search)
                    ->orWhere('barangay', 'LIKE', $search)
                    ->orWhere('position', 'LIKE', $search)
                    ->orWhere('committee', 'LIKE', $search)
                    ->orWhere('organization', 'LIKE', $search);
            });
        }

        $query->orderBy('name', 'asc');

        $perPage = (int) $request->input('per_page', 1000);
        $members = $query->paginate($perPage);

        return LydcMemberResource::collection($members)->response();
    }
}
