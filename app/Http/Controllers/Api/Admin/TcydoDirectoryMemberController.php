<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TcydoDirectoryMemberResource;
use App\Models\TcydoDirectoryMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TcydoDirectoryMemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TcydoDirectoryMember::query();

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

        return TcydoDirectoryMemberResource::collection($members)->response();
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

        $member = TcydoDirectoryMember::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'TCYDO Directory Member added successfully.',
            'data' => TcydoDirectoryMemberResource::make($member),
        ], 201);
    }

    public function show(TcydoDirectoryMember $tcydoDirectoryMember): JsonResponse
    {
        return response()->json(TcydoDirectoryMemberResource::make($tcydoDirectoryMember));
    }

    public function destroy(TcydoDirectoryMember $tcydoDirectoryMember): JsonResponse
    {
        $tcydoDirectoryMember->delete();

        return response()->json([
            'success' => true,
            'message' => 'TCYDO Directory Member removed successfully.',
        ]);
    }
}
