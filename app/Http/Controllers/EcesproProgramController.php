<?php

namespace App\Http\Controllers;

use App\Models\EcesproProgram;
use Illuminate\Http\Request;

class EcesproProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproProgram::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'school_year' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'slots' => 'required|integer|min:1',
            'status' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        return EcesproProgram::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproProgram $ecesproProgram)
    {
        return $ecesproProgram;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproProgram $ecesproProgram)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'school_year' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'slots' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string',
            'remarks' => 'nullable|string',
        ]);

        $ecesproProgram->update($validated);

        return $ecesproProgram;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproProgram $ecesproProgram)
    {
        $ecesproProgram->delete();

        return response()->noContent();
    }
}
