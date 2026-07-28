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
            'applicants' => 'nullable|array',
            'applicants.*.applicantId' => 'required|exists:ecespro_applications,id'
        ]);

        $batch = EcesproExamBatch::create([
            'batch_name' => $validated['batch_name'],
            'exam_date' => $validated['exam_date'],
            'time' => $validated['time'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'status' => $validated['status'] ?? null,
        ]);

        if (isset($validated['applicants'])) {
            foreach ($validated['applicants'] as $applicant) {
                \App\Models\EcesproExamination::create([
                    'ecespro_exam_batch_id' => $batch->id,
                    'ecespro_application_id' => $applicant['applicantId'],
                    'status' => 'Pending'
                ]);
            }
        }

        return $batch;
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
