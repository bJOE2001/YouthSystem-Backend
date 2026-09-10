<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ScholarPositionResource;
use App\Models\ScholarPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScholarPositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ScholarPosition::query()->withCount('scholars');

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['id', 'name', 'can_scan', 'status', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, strtolower($sortOrder) === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($request->boolean('all')) {
            return ScholarPositionResource::collection($query->get())->response();
        }

        $perPage = (int) $request->input('per_page', 15);
        $positions = $query->paginate($perPage);

        return ScholarPositionResource::collection($positions)->response();
    }

    public function active(): JsonResponse
    {
        $positions = ScholarPosition::active()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => ScholarPositionResource::collection($positions),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:scholar_positions,name',
            'can_scan' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $validated['can_scan'] = $validated['can_scan'] ?? false;
        $validated['status'] = $validated['status'] ?? 'active';

        $position = ScholarPosition::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Scholar position created successfully.',
            'data' => ScholarPositionResource::make($position),
        ], 201);
    }

    public function show(ScholarPosition $scholarPosition): JsonResponse
    {
        $scholarPosition->loadCount('scholars');

        return response()->json(ScholarPositionResource::make($scholarPosition));
    }

    public function update(Request $request, ScholarPosition $scholarPosition): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('scholar_positions', 'name')->ignore($scholarPosition->id),
            ],
            'can_scan' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $scholarPosition->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Scholar position updated successfully.',
            'data' => ScholarPositionResource::make($scholarPosition),
        ]);
    }

    public function destroy(ScholarPosition $scholarPosition): JsonResponse
    {
        $scholarPosition->scholars()->update(['scholar_position_id' => null]);
        $scholarPosition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Scholar position deleted successfully.',
        ]);
    }
}
