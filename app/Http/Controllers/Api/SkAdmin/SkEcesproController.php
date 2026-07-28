<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Http\Controllers\Controller;
use App\Models\EcesproApplication;
use App\Models\EcesproProgram;
use App\Models\EcesproRequirement;
use App\Models\EcesproScholar;
use App\Models\EcesproSetting;
use Illuminate\Http\Request;

class SkEcesproController extends Controller
{
    public function getSettings()
    {
        $settings = EcesproSetting::all()->pluck('value', 'key');
        $requirements = EcesproRequirement::all()->pluck('name');

        return response()->json([
            'data' => [
                'benefits' => $settings->get('benefits', []),
                'eligibility' => $settings->get('eligibility', []),
                'requirements' => $requirements,
            ],
        ]);
    }

    /**
     * Get the currently active ECESPRO Program details.
     */
    public function activeProgram()
    {
        $program = EcesproProgram::where('status', 'Open')->latest()->first();

        if (! $program) {
            return response()->json([
                'message' => 'No active scholarship program at the moment.',
                'program' => null,
            ]);
        }

        return response()->json([
            'program' => $program,
        ]);
    }

    /**
     * Get the authenticated user's active application.
     */
    public function myApplication(Request $request)
    {
        $application = EcesproApplication::where('user_id', $request->user()->id)
            ->latest()
            ->first();

        $scholar = EcesproScholar::where('user_id', $request->user()->id)->first();

        return response()->json([
            'application' => $application,
            'scholar' => $scholar,
        ]);
    }

    /**
     * Apply for the active ECESPRO Scholarship.
     */
    public function apply(Request $request)
    {
        $program = EcesproProgram::where('status', 'Open')->latest()->first();

        if (! $program) {
            return response()->json(['message' => 'No active scholarship program.'], 400);
        }

        $existingApplication = EcesproApplication::where('user_id', $request->user()->id)
            ->where('ecespro_program_id', $program->id)
            ->first();

        if ($existingApplication) {
            return response()->json(['message' => 'You have already applied for this program.'], 400);
        }

        $validated = $request->validate([
            // Personal Info
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'suffix' => 'nullable|string',
            'gender' => 'required|string',
            'birthdate' => 'required|date',
            'age' => 'required|integer',
            'place_of_birth' => 'required|string',
            'sex' => 'required|string',
            'civil_status' => 'required|string',
            'citizenship' => 'required|string',
            'personal_zip_code' => 'required|string',
            'ip_or_muslim' => 'nullable|string',
            'type_of_disability' => 'nullable|string',
            'mobile_number' => 'required|string',
            'email_address' => 'required|email',
            'permanent_mailing_address' => 'required|string',

            // Educational Info
            'previous_grade_college_year_level' => 'required|string',
            'general_average' => 'required|numeric',
            'school_attended_to_enroll' => 'nullable|string',
            'school_address' => 'required|string',
            'course_intended_to_enroll' => 'nullable|string',
            'type_of_school' => 'required|string',
            'school_year' => 'required|string',
            'school_citizenship' => 'nullable|string',
            'school' => 'required|string',
            'year_level' => 'required|string',
            'course' => 'required|string',
            'school_zip_code' => 'required|string',

            // Requirements files
            'certificate_of_indigency' => 'nullable|file',
            'report_card_grades' => 'nullable|file',
            'certificate_of_enrollment' => 'nullable|file',
            'certificate_of_registration' => 'nullable|file',
            'good_moral_certificate' => 'nullable|file',
            'barangay_clearance' => 'nullable|file',
            'other_supporting_documents' => 'nullable|file',
        ]);

        $files = [
            'certificate_of_indigency',
            'report_card_grades',
            'certificate_of_enrollment',
            'certificate_of_registration',
            'good_moral_certificate',
            'barangay_clearance',
            'other_supporting_documents',
        ];

        foreach ($files as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $path = $request->file($fileKey)->store('ecespro/applications', 'public');
                $validated[$fileKey] = $path;
            }
        }

        $validated['user_id'] = $request->user()->id;
        $validated['ecespro_program_id'] = $program->id;
        $validated['application_status'] = 'Submitted';

        $application = EcesproApplication::create($validated);

        return response()->json([
            'message' => 'Application submitted successfully',
            'application' => $application,
        ], 201);
    }

    /**
     * Get my semester requirements history.
     */
    public function myRequirements(Request $request)
    {
        $scholar = EcesproScholar::where('user_id', $request->user()->id)->first();

        if (! $scholar) {
            return response()->json([
                'message' => 'You are not an active scholar.',
                'requirements_history' => [],
            ]);
        }

        return response()->json([
            'requirements_history' => $scholar->requirements_history ?? [],
        ]);
    }

    /**
     * Submit semester requirements.
     */
    public function submitRequirements(Request $request)
    {
        $scholar = EcesproScholar::where('user_id', $request->user()->id)->first();

        if (! $scholar) {
            return response()->json(['message' => 'You are not an active scholar.'], 400);
        }

        $validated = $request->validate([
            'schoolYear' => 'required|string',
            'semester' => 'required|string',
            'uploadGrades' => 'nullable|file',
            'uploadCertificateOfEnrollment' => 'nullable|file',
            'uploadCertificateOfRegistration' => 'nullable|file',
            'uploadOtherSupportingDocuments' => 'nullable|file',
        ]);

        $documents = [
            'uploadGrades' => 'Grades',
            'uploadCertificateOfEnrollment' => 'Certificate of Enrollment',
            'uploadCertificateOfRegistration' => 'Certificate of Registration',
            'uploadOtherSupportingDocuments' => 'Other Supporting Documents',
        ];

        $history = $scholar->requirements_history ?? [];

        foreach ($documents as $fileKey => $documentType) {
            if ($request->hasFile($fileKey)) {
                $path = $request->file($fileKey)->store('ecespro/requirements', 'public');

                $history[] = [
                    'id' => uniqid(),
                    'dateSubmitted' => now()->format('Y-m-d'),
                    'schoolYear' => $validated['schoolYear'],
                    'semester' => $validated['semester'],
                    'documentType' => $documentType,
                    'fileName' => $request->file($fileKey)->getClientOriginalName(),
                    'filePath' => $path,
                    'status' => 'Pending',
                    'remarks' => '',
                ];
            }
        }

        $scholar->update(['requirements_history' => $history]);

        return response()->json([
            'message' => 'Requirements submitted successfully.',
            'requirements_history' => $history,
        ]);
    }
}
