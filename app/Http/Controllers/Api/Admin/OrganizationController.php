<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Organization::query()->withCount('youthProfiles');

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
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
            return OrganizationResource::collection($query->get())->response();
        }

        $perPage = (int) $request->input('per_page', 15);
        $organizations = $query->paginate($perPage);

        return OrganizationResource::collection($organizations)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:organizations,name',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';

        $organization = Organization::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Organization created successfully.',
            'data' => OrganizationResource::make($organization),
        ], 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        $organization->loadCount('youthProfiles');

        return response()->json(OrganizationResource::make($organization));
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organizations', 'name')->ignore($organization->id),
            ],
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $organization->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Organization updated successfully.',
            'data' => OrganizationResource::make($organization),
        ]);
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $organization->youthProfiles()->update(['organization_id' => null]);
        $organization->delete();

        return response()->json([
            'success' => true,
            'message' => 'Organization deleted successfully.',
        ]);
    }
}
