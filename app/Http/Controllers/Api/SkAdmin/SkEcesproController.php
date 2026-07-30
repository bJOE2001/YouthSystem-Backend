<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Http\Controllers\Controller;
use App\Models\EcesproApplication;
use App\Models\EcesproProgram;
use App\Models\EcesproRequirement;
use App\Models\EcesproScholar;
use App\Models\EcesproSetting;
use App\Notifications\EcesproApplicationStatusNotification;
use Illuminate\Http\Request;

class SkEcesproController extends Controller
{
    public function getSettings()
    {
        $settings = EcesproSetting::all()->pluck('value', 'key');
        $requirements = EcesproRequirement::where('status', 'Active')->get();

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
        $application = EcesproApplication::with(['examination.batch', 'interview.batch', 'contract'])
            ->where('user_id', $request->user()->id)
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
            // Personal
            'first_name' => 'nullable|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'suffix' => 'nullable|string',
            'gender' => 'nullable|string',
            'birthdate' => 'nullable|string',
            'age' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            'sex' => 'nullable|string',
            'civil_status' => 'nullable|string',
            'citizenship' => 'nullable|string',
            'personal_zip_code' => 'nullable|string',
            'ip_or_muslim' => 'nullable|string',
            'type_of_disability' => 'nullable|string',
            'mobile_number' => 'nullable|string',
            'email_address' => 'nullable|string',
            'permanent_mailing_address' => 'nullable|string',

            // Educational
            'year_level' => 'nullable|string',
            'general_average' => 'nullable|string',
            'school_intended_to_enroll' => 'nullable|string',
            'school_address' => 'nullable|string',
            'course_intended_to_enroll' => 'nullable|string',
            'type_of_school' => 'nullable|string',
            'school_year' => 'nullable|string',
            'course' => 'nullable|string',

            // Father
            'father_last_name' => 'nullable|string',
            'father_middle_name' => 'nullable|string',
            'father_first_name' => 'nullable|string',
            'father_address' => 'nullable|string',
            'father_occupation' => 'nullable|string',
            'father_educational_attainment' => 'nullable|string',

            // Mother
            'mother_first_name' => 'nullable|string',
            'mother_maiden_middle_name' => 'nullable|string',
            'mother_maiden_last_name' => 'nullable|string',
            'mother_occupation' => 'nullable|string',
            'mother_educational_attainment' => 'nullable|string',

            // Guardian
            'guardian_first_name' => 'nullable|string',
            'guardian_maiden_middle_name' => 'nullable|string',
            'guardian_maiden_last_name' => 'nullable|string',
            'guardian_occupation' => 'nullable|string',
            'guardian_educational_attainment' => 'nullable|string',

            // Other
            'parents_guardian_total_income' => 'nullable|string',
            'number_of_siblings_in_family' => 'nullable|string',
            'parents_marital_status' => 'nullable|string',
        ]);

        $activeRequirements = EcesproRequirement::where('status', 'Active')->get();
        $requirementRules = [];

        foreach ($activeRequirements as $req) {
            $rule = $req->required_status === 'Required' ? 'required|file' : 'nullable|file';
            $requirementRules['requirement_'.$req->id] = $rule;
        }

        $validatedRequirements = $request->validate($requirementRules);

        $submittedRequirements = [];

        foreach ($activeRequirements as $req) {
            $fileKey = 'requirement_'.$req->id;
            if ($request->hasFile($fileKey)) {
                $path = $request->file($fileKey)->store('ecespro/applications', 'public');
                $submittedRequirements[] = [
                    'id' => $req->id,
                    'name' => $req->name,
                    'path' => $path,
                ];
            }
        }

        $mappedData = [
            'first_name' => $validated['first_name'] ?? null,
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'birthdate' => $validated['birthdate'] ?? null,
            'age' => $validated['age'] ?? null,
            'place_of_birth' => $validated['place_of_birth'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'citizenship' => $validated['citizenship'] ?? null,
            'personal_zip_code' => $validated['personal_zip_code'] ?? null,
            'ip_or_muslim' => $validated['ip_or_muslim'] ?? null,
            'type_of_disability' => $validated['type_of_disability'] ?? null,
            'mobile_number' => $validated['mobile_number'] ?? null,
            'email_address' => $validated['email_address'] ?? null,
            'permanent_mailing_address' => $validated['permanent_mailing_address'] ?? null,

            'previous_grade_college_year_level' => $validated['year_level'] ?? null,
            'general_average' => $validated['general_average'] ?? null,
            'school_attended_to_enroll' => $validated['school_intended_to_enroll'] ?? null,
            'school_address' => $validated['school_address'] ?? null,
            'course_intended_to_enroll' => $validated['course_intended_to_enroll'] ?? null,
            'type_of_school' => $validated['type_of_school'] ?? null,
            'school_year' => $validated['school_year'] ?? null,
            'course' => $validated['course'] ?? null,

            'father_last_name' => $validated['father_last_name'] ?? null,
            'father_first_name' => $validated['father_first_name'] ?? null,
            'father_address' => $validated['father_address'] ?? null,
            'father_occupation' => $validated['father_occupation'] ?? null,
            'father_educational_attainment' => $validated['father_educational_attainment'] ?? null,

            'mother_maiden_middle_name' => $validated['mother_maiden_middle_name'] ?? null,
            'mother_maiden_last_name' => $validated['mother_maiden_last_name'] ?? null,
            'mother_occupation' => $validated['mother_occupation'] ?? null,
            'mother_educational_attainment' => $validated['mother_educational_attainment'] ?? null,

            'guardian_maiden_middle_name' => $validated['guardian_maiden_middle_name'] ?? null,
            'guardian_maiden_last_name' => $validated['guardian_maiden_last_name'] ?? null,
            'guardian_occupation' => $validated['guardian_occupation'] ?? null,
            'guardian_educational_attainment' => $validated['guardian_educational_attainment'] ?? null,

            'parents_guardian_total_income' => $validated['parents_guardian_total_income'] ?? null,
            'number_of_siblings_in_family' => $validated['number_of_siblings_in_family'] ?? null,
            'parents_marital_status' => $validated['parents_marital_status'] ?? null,

            'user_id' => $request->user()->id,
            'ecespro_program_id' => $program->id,
            'application_status' => 'Submitted',
            'submitted_requirements' => $submittedRequirements,
        ];

        $application = EcesproApplication::create($mappedData);

        $request->user()->notify(new EcesproApplicationStatusNotification($application, 'Submitted'));

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
            $app = EcesproApplication::where('user_id', $request->user()->id)->first();
            if ($app && in_array($app->application_status, ['Approved', 'Qualified for Contract', 'Contract Scheduled'])) {
                $initialReqs = array_map(function ($req) use ($app) {
                    return [
                        'id' => $req['id'] ?? uniqid(),
                        'dateSubmitted' => isset($app->created_at) ? $app->created_at->format('Y-m-d') : now()->format('Y-m-d'),
                        'schoolYear' => $app->school_year ?? '2025-2026',
                        'semester' => '1st Semester',
                        'documentType' => $req['name'] ?? 'Initial Requirement Document',
                        'fileName' => isset($req['path']) ? basename($req['path']) : 'document.pdf',
                        'filePath' => $req['path'] ?? '',
                        'status' => 'Validated',
                        'remarks' => 'Initial Application Requirement',
                    ];
                }, $app->submitted_requirements ?? []);

                $scholar = EcesproScholar::firstOrCreate(
                    ['user_id' => $request->user()->id],
                    [
                        'ecespro_application_id' => $app->id,
                        'scholar_no' => 'SCH-'.str_pad($app->id, 4, '0', STR_PAD_LEFT),
                        'school' => $app->school_intended_to_enroll ?? $app->school_attended_to_enroll ?? 'N/A',
                        'course' => $app->course_intended_to_enroll ?? $app->course ?? 'N/A',
                        'status' => 'Active',
                        'compliance_status' => 'Compliant',
                        'requirements_history' => $initialReqs,
                    ]
                );
            }
        }

        return response()->json([
            'requirements_history' => $scholar ? ($scholar->requirements_history ?? []) : [],
        ]);
    }

    /**
     * Submit semester requirements.
     */
    public function submitRequirements(Request $request)
    {
        $scholar = EcesproScholar::where('user_id', $request->user()->id)->first();

        if (! $scholar) {
            $app = EcesproApplication::where('user_id', $request->user()->id)->first();
            if ($app && in_array($app->application_status, ['Approved', 'Qualified for Contract', 'Contract Scheduled'])) {
                $scholar = EcesproScholar::firstOrCreate(
                    ['user_id' => $request->user()->id],
                    [
                        'ecespro_application_id' => $app->id,
                        'scholar_no' => 'SCH-'.str_pad($app->id, 4, '0', STR_PAD_LEFT),
                        'school' => $app->school_intended_to_enroll ?? $app->school_attended_to_enroll ?? 'N/A',
                        'course' => $app->course_intended_to_enroll ?? $app->course ?? 'N/A',
                        'status' => 'Active',
                        'compliance_status' => 'Compliant',
                        'requirements_history' => [],
                    ]
                );
            }
        }

        if (! $scholar) {
            return response()->json(['message' => 'You must have an approved scholarship application to submit semester requirements.'], 400);
        }

        $validated = $request->validate([
            'schoolYear' => 'required|string',
            'semester' => 'required|string',
            'generalAverage' => 'nullable|string',
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
                    'generalAverage' => $validated['generalAverage'] ?? null,
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
