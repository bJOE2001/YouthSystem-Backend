<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CenterOfParticipationResource;
use App\Models\CenterOfParticipation;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CenterOfParticipationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CenterOfParticipation::query();

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
        if (request()-> boolean('all')){
            return CenterOfParticipationResource::collection($query->get())->response();

        }
        $perPage = (int) $request->input('per_page', 15);
        $center = $query->paginate($perPage);

        return CenterOfParticipationResource::collection($center)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:center_of_participations,name',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $center = CenterOfParticipation::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Center of Participation created successfully.',
            'data' => CenterOfParticipationResource::make($center),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    // public function show(string $id)
    // {
    //     //
    // }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CenterOfParticipation $center_of_participation): JsonResponse
    {
        $validated = $request->validated([
             'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('center_of_participations', 'name')->ignore($center_of_participation->id),
            ],
            'status' => 'sometimes|string|in:active,inactive',
        ]);
            $center_of_participation->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Center of Participation updated successfully.',
                'data' => CenterOfParticipationResource::make($center_of_participation),

            ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CenterOfParticipation $center_of_participation): JsonResponse
    {
        $center_of_participation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Center of Participation deleted successfully.',
        ]);
    }
}
