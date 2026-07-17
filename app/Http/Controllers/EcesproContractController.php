<?php

namespace App\Http\Controllers;

use App\Models\EcesproContract;
use Illuminate\Http\Request;

class EcesproContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproContract::with('application')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ecespro_application_id' => 'required|exists:ecespro_applications,id',
            'schedule' => 'nullable|string',
            'guardian' => 'nullable|string',
            'documents_status' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        return EcesproContract::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproContract $ecesproContract)
    {
        return $ecesproContract->load('application');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproContract $ecesproContract)
    {
        $validated = $request->validate([
            'ecespro_application_id' => 'sometimes|exists:ecespro_applications,id',
            'schedule' => 'nullable|string',
            'guardian' => 'nullable|string',
            'documents_status' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $ecesproContract->update($validated);

        return $ecesproContract;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproContract $ecesproContract)
    {
        $ecesproContract->delete();

        return response()->noContent();
    }
}
