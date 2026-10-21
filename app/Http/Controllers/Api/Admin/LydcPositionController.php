<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LydcPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LydcPositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LydcPosition::query();

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
        $positions = LydcPosition::where('status', 'active')->orderBy('name')->get();
        return response()->json(['data' => $positions]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:lydc_positions,name',
            'status' => 'required|string|in:active,inactive',
        ]);

        $position = LydcPosition::create($validated);
        return response()->json($position, 201);
    }

    public function show(LydcPosition $lydcPosition): JsonResponse
    {
        return response()->json($lydcPosition);
    }

    public function update(Request $request, LydcPosition $lydcPosition): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lydc_positions', 'name')->ignore($lydcPosition->id),
            ],
            'status' => 'required|string|in:active,inactive',
        ]);

        $lydcPosition->update($validated);
        return response()->json($lydcPosition);
    }

    public function destroy(LydcPosition $lydcPosition): JsonResponse
    {
        $lydcPosition->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
