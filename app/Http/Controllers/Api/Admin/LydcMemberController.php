<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LydcMemberResource;
use App\Models\LydcMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LydcMemberController extends Controller
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

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->input('per_page', 10);
        $members = $query->paginate($perPage);

        return LydcMemberResource::collection($members)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'initials' => 'nullable|string|max:10',
            'barangay' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'committee' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:255',
            'responsibilities' => 'nullable|string',
            'status' => 'nullable|string|max:50',
        ]);

        $member = LydcMember::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'LYDC Member added successfully.',
            'data' => LydcMemberResource::make($member),
        ], 201);
    }

    public function show(LydcMember $lydcMember): JsonResponse
    {
        return response()->json(LydcMemberResource::make($lydcMember));
    }

    public function destroy(LydcMember $lydcMember): JsonResponse
    {
        $lydcMember->delete();

        return response()->json([
            'success' => true,
            'message' => 'LYDC Member removed successfully.',
        ]);
    }
}
