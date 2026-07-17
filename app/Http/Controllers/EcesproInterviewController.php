<?php

namespace App\Http\Controllers;

use App\Models\EcesproInterview;
use Illuminate\Http\Request;

class EcesproInterviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproInterview::with(['application', 'batch'])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ecespro_application_id' => 'required|exists:ecespro_applications,id',
            'ecespro_interview_batch_id' => 'nullable|exists:ecespro_interview_batches,id',
            'remarks' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        return EcesproInterview::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproInterview $ecesproInterview)
    {
        return $ecesproInterview->load(['application', 'batch']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproInterview $ecesproInterview)
    {
        $validated = $request->validate([
            'ecespro_application_id' => 'sometimes|exists:ecespro_applications,id',
            'ecespro_interview_batch_id' => 'nullable|exists:ecespro_interview_batches,id',
            'remarks' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $ecesproInterview->update($validated);

        return $ecesproInterview;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproInterview $ecesproInterview)
    {
        $ecesproInterview->delete();

        return response()->noContent();
    }
}
