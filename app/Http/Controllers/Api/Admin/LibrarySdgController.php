<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SdgLibraryResource;
use App\Models\Sdg;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LibrarySdgController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sdg::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['id', 'name', 'status', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, strtolower($sortOrder) === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('id', 'asc');
        }

        if ($request->boolean('all')) {
            return SdgLibraryResource::collection($query->get())->response();
        }

        $perPage = (int) $request->input('per_page', 15);
        $sdgs = $query->paginate($perPage);

        return SdgLibraryResource::collection($sdgs)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:library_sdgs,name',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $sdg = Sdg::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'SDG created successfully.',
            'data' => SdgLibraryResource::make($sdg),
        ], 201);
    }

    public function update(Request $request, Sdg $library_sdg): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('library_sdgs', 'name')->ignore($library_sdg->id),
            ],
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $library_sdg->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'SDG updated successfully.',
            'data' => SdgLibraryResource::make($library_sdg),
        ]);
    }

    public function destroy(Sdg $library_sdg): JsonResponse
    {
        $library_sdg->delete();

        return response()->json([
            'success' => true,
            'message' => 'SDG deleted successfully.',
        ]);
    }
}
