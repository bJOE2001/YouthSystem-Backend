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

    /**
     * Get all semester compliance requirement submissions across scholars.
     */
    public function complianceValidations(Request $request)
    {
        $scholars = EcesproScholar::with(['user', 'application'])->get();
        $submissions = [];

        foreach ($scholars as $scholar) {
            $history = $scholar->requirements_history ?? [];
            if (! is_array($history)) {
                continue;
            }

            foreach ($history as $index => $item) {
                $files = [];
                if (! empty($item['files']) && is_array($item['files'])) {
                    $files = $item['files'];
                } elseif (! empty($item['filePath'])) {
                    $files = [[
                        'name' => $item['fileName'] ?? $item['documentType'] ?? 'Requirement Document',
                        'url' => $item['filePath'],
                    ]];
                }

                $submissions[] = [
                    'scholarId' => $scholar->id,
                    'scholarNo' => $scholar->scholar_no,
                    'scholarName' => $scholar->user?->name ?? $scholar->application?->full_name ?? ('Scholar #'.$scholar->id),
                    'email' => $scholar->user?->email ?? $scholar->application?->email ?? '',
                    'school' => $scholar->school ?? $scholar->application?->school_name ?? '',
                    'course' => $scholar->course ?? $scholar->application?->course ?? '',
                    'historyIndex' => $index,
                    'submittedAt' => $item['submitted_at'] ?? $item['dateSubmitted'] ?? null,
                    'schoolYear' => $item['school_year'] ?? $item['schoolYear'] ?? '',
                    'semester' => $item['semester'] ?? '',
                    'generalAverage' => $item['general_average'] ?? $item['generalAverage'] ?? null,
                    'status' => $item['status'] ?? 'Pending',
                    'remarks' => $item['remarks'] ?? '',
                    'files' => $files,
                ];
            }
        }

        // Sort latest submitted first
        usort($submissions, function ($a, $b) {
            return strcmp($b['submittedAt'] ?? '', $a['submittedAt'] ?? '');
        });

        return response()->json(['data' => $submissions]);
    }

    /**
     * Review/Validate a specific scholar requirement submission.
     */
    public function reviewCompliance(Request $request, EcesproScholar $ecesproScholar)
    {
        $request->validate([
            'historyIndex' => 'required|integer',
            'status' => 'required|string|in:Approved,For Resubmission,Disapproved,Pending,Verified',
            'remarks' => 'nullable|string',
        ]);

        $index = $request->input('historyIndex');
        $status = $request->input('status');
        $remarks = $request->input('remarks', '');

        $history = $ecesproScholar->requirements_history ?? [];
        if (! isset($history[$index])) {
            return response()->json(['message' => 'Submission record not found.'], 404);
        }

        $history[$index]['status'] = $status;
        $history[$index]['remarks'] = $remarks;
        $history[$index]['reviewed_at'] = now()->toIso8601String();

        $ecesproScholar->requirements_history = $history;
        $ecesproScholar->compliance_status = $status;
        $ecesproScholar->save();

        return response()->json([
            'message' => 'Compliance requirement updated successfully.',
            'scholar' => $ecesproScholar->load(['user', 'application']),
        ]);
    }
}
