<?php

namespace App\Http\Controllers;

use App\Models\EcesproApplication;
use App\Models\EcesproContract;
use App\Models\EcesproScholar;
use App\Notifications\EcesproApplicationStatusNotification;
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
     * Mark the contract as signed and activate the scholar directly from application.
     */
    public function signApplication(Request $request, $applicationId)
    {
        $application = EcesproApplication::findOrFail($applicationId);

        $contract = EcesproContract::create([
            'ecespro_application_id' => $application->id,
            'schedule' => null,
            'guardian' => null,
            'status' => 'Signed',
            'documents_status' => 'Submitted',
        ]);

        $application->update(['application_status' => 'Approved']);

        $scholar = EcesproScholar::firstOrCreate(
            ['ecespro_application_id' => $application->id],
            [
                'user_id' => $application->user_id,
                'scholar_no' => 'PENDING',
                'school' => $application->school_intended_to_enroll ?? $application->school_attended_to_enroll ?? 'N/A',
                'course' => $application->course_intended_to_enroll ?? $application->course ?? 'N/A',
                'status' => 'Active',
                'compliance_status' => 'Compliant',
                'requirements_history' => [],
            ]
        );

        if ($scholar->wasRecentlyCreated || $scholar->scholar_no === 'PENDING' || empty($scholar->scholar_no)) {
            $scholar->scholar_no = 'SCH-'.str_pad($scholar->id, 4, '0', STR_PAD_LEFT);
            $scholar->save();
        }

        if ($user = $application->user) {
            $metadata = [
                'scholar_no' => $scholar->scholar_no,
                'school' => $scholar->school,
                'course' => $scholar->course,
            ];
            $user->notify(new EcesproApplicationStatusNotification(
                $application,
                'Approved',
                'Your contract has been signed and you are now an official scholar.',
                $metadata
            ));
        }

        return response()->json([
            'message' => 'Contract signed successfully',
            'contract' => $contract->load('application'),
        ]);
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
