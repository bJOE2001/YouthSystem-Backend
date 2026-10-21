<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TcydoDirectoryPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TcydoDirectoryPositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TcydoDirectoryPosition::query();

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        if ($request->boolean('all')) {
            return response()->json($query->get());
        }

        $perPage = (int) $request->input('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function active(): JsonResponse
    {
        $positions = TcydoDirectoryPosition::where('status', 'active')->orderBy('name')->get();
        return response()->json(['data' => $positions]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tcydo_directory_positions,name',
            'status' => 'required|string|in:active,inactive',
        ]);

        $position = TcydoDirectoryPosition::create($validated);
        return response()->json($position, 201);
    }

    public function show(TcydoDirectoryPosition $tcydoDirectoryPosition): JsonResponse
    {
        return response()->json($tcydoDirectoryPosition);
    }

    public function update(Request $request, TcydoDirectoryPosition $tcydoDirectoryPosition): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tcydo_directory_positions', 'name')->ignore($tcydoDirectoryPosition->id),
            ],
            'status' => 'required|string|in:active,inactive',
        ]);

        $tcydoDirectoryPosition->update($validated);
        return response()->json($tcydoDirectoryPosition);
    }

    public function destroy(TcydoDirectoryPosition $tcydoDirectoryPosition): JsonResponse
    {
        $tcydoDirectoryPosition->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
