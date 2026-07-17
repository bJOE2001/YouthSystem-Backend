<?php

namespace App\Http\Controllers;

use App\Models\EcesproExamination;
use Illuminate\Http\Request;

class EcesproExaminationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproExamination::with(['application', 'batch'])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ecespro_application_id' => 'required|exists:ecespro_applications,id',
            'ecespro_exam_batch_id' => 'nullable|exists:ecespro_exam_batches,id',
            'score' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        return EcesproExamination::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproExamination $ecesproExamination)
    {
        return $ecesproExamination->load(['application', 'batch']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproExamination $ecesproExamination)
    {
        $validated = $request->validate([
            'ecespro_application_id' => 'sometimes|exists:ecespro_applications,id',
            'ecespro_exam_batch_id' => 'nullable|exists:ecespro_exam_batches,id',
            'score' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $ecesproExamination->update($validated);

        return $ecesproExamination;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproExamination $ecesproExamination)
    {
        $ecesproExamination->delete();

        return response()->noContent();
    }
}
