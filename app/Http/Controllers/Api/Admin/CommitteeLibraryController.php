<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CommitteeResource;
use App\Models\Committee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommitteeLibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Committee::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%'.$search.'%')
                    ->orWhere('description', 'LIKE', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['id', 'name', 'status', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, strtolower($sortOrder) === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($request->boolean('all')) {
            return CommitteeResource::collection($query->get())->response();
        }

        $perPage = (int) $request->input('per_page', 15);
        $committees = $query->paginate($perPage);

        return CommitteeResource::collection($committees)->response();
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $committees = Committee::active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CommitteeResource::collection($committees),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:committees,name',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['code'] = Str::slug($validated['name'], '_');

        $committee = Committee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Committee created successfully.',
            'data' => CommitteeResource::make($committee),
        ], 201);
    }

    public function show(Committee $committee): JsonResponse
    {
        return response()->json(CommitteeResource::make($committee));
    }

    public function update(Request $request, Committee $committee): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('committees', 'name')->ignore($committee->id),
            ],
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        if (isset($validated['name'])) {
            $validated['code'] = Str::slug($validated['name'], '_');
        }

        $committee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Committee updated successfully.',
            'data' => CommitteeResource::make($committee),
        ]);
    }

    public function destroy(Committee $committee): JsonResponse
    {
        $committee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Committee deleted successfully.',
        ]);
    }
}
