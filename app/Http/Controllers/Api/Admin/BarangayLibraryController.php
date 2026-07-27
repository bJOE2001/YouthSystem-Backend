<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\BarangayLibraryResource;
use App\Models\Barangay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangayLibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Barangay::query();

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['id', 'name', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, strtolower($sortOrder) === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($request->boolean('all')) {
            return BarangayLibraryResource::collection($query->get())->response();
        }

        $perPage = (int) $request->input('per_page', 15);
        $barangays = $query->paginate($perPage);

        return BarangayLibraryResource::collection($barangays)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:barangays,name',
        ]);

        $barangay = Barangay::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Barangay created successfully.',
            'data' => BarangayLibraryResource::make($barangay),
        ], 201);
    }

    public function show(Barangay $barangay): JsonResponse
    {
        return response()->json(BarangayLibraryResource::make($barangay));
    }

    public function update(Request $request, Barangay $barangay): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('barangays', 'name')->ignore($barangay->id),
            ],
        ]);

        $barangay->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Barangay updated successfully.',
            'data' => BarangayLibraryResource::make($barangay),
        ]);
    }

    public function destroy(Barangay $barangay): JsonResponse
    {
        $barangay->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barangay deleted successfully.',
        ]);
    }
}
