<?php

namespace App\Http\Controllers;

use App\Models\EcesproExamBatch;
use Illuminate\Http\Request;

class EcesproExamBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproExamBatch::with('examinations.application')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:255',
            'exam_date' => 'required|date',
            'time' => 'nullable|string',
            'venue' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        return EcesproExamBatch::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproExamBatch $ecesproExamBatch)
    {
        return $ecesproExamBatch->load('examinations.application');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproExamBatch $ecesproExamBatch)
    {
        $validated = $request->validate([
            'batch_name' => 'sometimes|string|max:255',
            'exam_date' => 'sometimes|date',
            'time' => 'nullable|string',
            'venue' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $ecesproExamBatch->update($validated);

        return $ecesproExamBatch;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproExamBatch $ecesproExamBatch)
    {
        $ecesproExamBatch->delete();

        return response()->noContent();
    }
}
