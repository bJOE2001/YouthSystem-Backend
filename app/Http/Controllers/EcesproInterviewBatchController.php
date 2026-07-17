<?php

namespace App\Http\Controllers;

use App\Models\EcesproInterviewBatch;
use Illuminate\Http\Request;

class EcesproInterviewBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproInterviewBatch::with('interviews.application')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:255',
            'interview_date' => 'required|date',
            'time' => 'nullable|string',
            'panel' => 'nullable|string',
            'mode' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        return EcesproInterviewBatch::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproInterviewBatch $ecesproInterviewBatch)
    {
        return $ecesproInterviewBatch->load('interviews.application');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproInterviewBatch $ecesproInterviewBatch)
    {
        $validated = $request->validate([
            'batch_name' => 'sometimes|string|max:255',
            'interview_date' => 'sometimes|date',
            'time' => 'nullable|string',
            'panel' => 'nullable|string',
            'mode' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $ecesproInterviewBatch->update($validated);

        return $ecesproInterviewBatch;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproInterviewBatch $ecesproInterviewBatch)
    {
        $ecesproInterviewBatch->delete();

        return response()->noContent();
    }
}
