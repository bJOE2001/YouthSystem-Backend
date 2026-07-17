<?php

namespace App\Http\Controllers;

use App\Models\EcesproScholar;
use Illuminate\Http\Request;

class EcesproScholarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproScholar::with(['user', 'application'])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'ecespro_application_id' => 'required|exists:ecespro_applications,id',
            'scholar_no' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'course' => 'nullable|string|max:255',
            'compliance_status' => 'nullable|string|max:255',
            'requirements_history' => 'nullable|array',
            'status' => 'nullable|string',
            'allowance_received_amount' => 'nullable|numeric|min:0',
        ]);

        return EcesproScholar::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproScholar $ecesproScholar)
    {
        return $ecesproScholar->load(['user', 'application']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproScholar $ecesproScholar)
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'ecespro_application_id' => 'sometimes|exists:ecespro_applications,id',
            'scholar_no' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'course' => 'nullable|string|max:255',
            'compliance_status' => 'nullable|string|max:255',
            'requirements_history' => 'nullable|array',
            'status' => 'nullable|string',
            'allowance_received_amount' => 'nullable|numeric|min:0',
        ]);

        $ecesproScholar->update($validated);

        return $ecesproScholar;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproScholar $ecesproScholar)
    {
        $ecesproScholar->delete();

        return response()->noContent();
    }
}
