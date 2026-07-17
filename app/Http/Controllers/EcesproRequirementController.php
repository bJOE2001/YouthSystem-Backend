<?php

namespace App\Http\Controllers;

use App\Models\EcesproRequirement;
use Illuminate\Http\Request;

class EcesproRequirementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproRequirement::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'accepted_files' => 'required|string',
            'required_status' => 'required|string',
            'status' => 'nullable|string',
        ]);

        return EcesproRequirement::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproRequirement $ecesproRequirement)
    {
        return $ecesproRequirement;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproRequirement $ecesproRequirement)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'accepted_files' => 'sometimes|string',
            'required_status' => 'sometimes|string',
            'status' => 'nullable|string',
        ]);

        $ecesproRequirement->update($validated);

        return $ecesproRequirement;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproRequirement $ecesproRequirement)
    {
        $ecesproRequirement->delete();

        return response()->noContent();
    }
}
