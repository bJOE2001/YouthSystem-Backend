<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Http\Controllers\Controller;
use App\Models\EcesproApplication;
use App\Models\EcesproComplianceSchedule;
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

        $history = $scholar ? ($scholar->requirements_history ?? []) : [];
        if (! empty($history) && is_array($history)) {
            $dedupedHistory = [];
            $seenKeys = [];

            for ($i = count($history) - 1; $i >= 0; $i--) {
                $item = $history[$i];
                $sy = strtolower(trim($item['schoolYear'] ?? $item['school_year'] ?? ''));
                $sem = strtolower(trim($item['semester'] ?? ''));
                $doc = strtolower(trim($item['documentType'] ?? $item['document_type'] ?? ''));
                $key = "{$sy}|{$sem}|{$doc}";

                if (! isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    array_unshift($dedupedHistory, $item);
                }
            }

            if (count($dedupedHistory) !== count($history)) {
                $scholar->update(['requirements_history' => $dedupedHistory]);
                $history = $dedupedHistory;
            }
        }

        return response()->json([
            'data' => $history,
            'requirements_history' => $history,
        ]);
    }

    /**
     * Delete a specific requirement history submission by index.
     */
    public function deleteRequirement(Request $request, $index)
    {
        $scholar = EcesproScholar::where('user_id', $request->user()->id)->first();
        if (! $scholar) {
            return response()->json(['message' => 'Scholar record not found.'], 404);
        }

        $history = $scholar->requirements_history ?? [];
        if (! is_array($history) || ! isset($history[$index])) {
            return response()->json(['message' => 'Requirement submission record not found.'], 404);
        }

        array_splice($history, (int) $index, 1);

        $scholar->requirements_history = array_values($history);
        $scholar->save();

        return response()->json([
            'message' => 'Requirement submission deleted successfully.',
            'requirements_history' => $scholar->requirements_history,
        ]);
    }

    /**
     * Get available compliance schedules for scholars.
     */
    public function complianceSchedules()
    {
        $schedules = EcesproComplianceSchedule::latest()->get()->map(function ($s) {
            $isClosed = strtolower($s->status) !== 'open' || ($s->end_date && now()->startOfDay()->gt($s->end_date));

            return [
                'id' => $s->id,
                'title' => $s->title,
                'schoolYear' => $s->school_year,
                'semester' => $s->semester,
                'startDate' => $s->start_date ? $s->start_date->format('Y-m-d') : null,
                'endDate' => $s->end_date ? $s->end_date->format('Y-m-d') : null,
                'status' => $s->status,
                'isClosed' => $isClosed,
                'instructions' => $s->instructions,
                'requiredDocuments' => $s->required_documents ?? [],
                'required_documents' => $s->required_documents ?? [],
            ];
        });

        return response()->json(['data' => $schedules]);
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

        // Check if compliance schedule for this schoolYear & semester is Open
        $schedule = EcesproComplianceSchedule::where('school_year', $validated['schoolYear'])
            ->where('semester', $validated['semester'])
            ->latest()
            ->first();

        if ($schedule) {
            $isClosed = strtolower($schedule->status) !== 'open' || ($schedule->end_date && now()->startOfDay()->gt($schedule->end_date));
            if ($isClosed) {
                return response()->json([
                    'message' => 'Submissions for "'.$schedule->title.'" are currently closed.',
                ], 403);
            }
        }

        $documents = [
            'uploadGrades' => 'Grades',
            'uploadCertificateOfEnrollment' => 'Certificate of Enrollment',
            'uploadCertificateOfRegistration' => 'Certificate of Registration',
            'uploadOtherSupportingDocuments' => 'Other Supporting Documents',
        ];

        $history = $scholar->requirements_history ?? [];

        $saveItem = function (&$historyArr, $schoolYear, $semester, $gpa, $docLabel, $fileName, $filePath) {
            $matchedIdx = null;
            $syTarget = strtolower(trim($schoolYear));
            $semTarget = strtolower(trim($semester));
            $docTarget = strtolower(trim($docLabel));

            foreach ($historyArr as $idx => $item) {
                $itemSy = strtolower(trim($item['schoolYear'] ?? $item['school_year'] ?? ''));
                $itemSem = strtolower(trim($item['semester'] ?? ''));
                $itemDoc = strtolower(trim($item['documentType'] ?? $item['document_type'] ?? ''));

                if ($itemSy === $syTarget && $itemSem === $semTarget && $itemDoc === $docTarget) {
                    $matchedIdx = $idx;
                    break;
                }
            }

            if ($matchedIdx !== null) {
                $historyArr[$matchedIdx]['dateSubmitted'] = now()->format('Y-m-d');
                $historyArr[$matchedIdx]['generalAverage'] = $gpa ?? $historyArr[$matchedIdx]['generalAverage'] ?? null;
                $historyArr[$matchedIdx]['fileName'] = $fileName;
                $historyArr[$matchedIdx]['filePath'] = $filePath;
                $historyArr[$matchedIdx]['status'] = 'Pending';
                $historyArr[$matchedIdx]['remarks'] = '';
            } else {
                $historyArr[] = [
                    'id' => uniqid(),
                    'dateSubmitted' => now()->format('Y-m-d'),
                    'schoolYear' => $schoolYear,
                    'semester' => $semester,
                    'generalAverage' => $gpa,
                    'documentType' => $docLabel,
                    'fileName' => $fileName,
                    'filePath' => $filePath,
                    'status' => 'Pending',
                    'remarks' => '',
                ];
            }
        };

        foreach ($documents as $fileKey => $documentType) {
            if ($request->hasFile($fileKey)) {
                $path = $request->file($fileKey)->store('ecespro/requirements', 'public');
                $saveItem(
                    $history,
                    $validated['schoolYear'],
                    $validated['semester'],
                    $validated['generalAverage'] ?? null,
                    $documentType,
                    $request->file($fileKey)->getClientOriginalName(),
                    $path
                );
            }
        }

        $labels = $request->input('requirementLabels', $request->input('requirement_labels', []));
        $reqFiles = $request->file('requirementFiles', $request->file('requirement_files', []));

        if (is_array($reqFiles)) {
            foreach ($reqFiles as $idx => $uploadedFile) {
                if ($uploadedFile && $uploadedFile->isValid()) {
                    $docLabel = $labels[$idx] ?? ('Requirement Document #'.($idx + 1));
                    $path = $uploadedFile->store('ecespro/requirements', 'public');
                    $saveItem(
                        $history,
                        $validated['schoolYear'],
                        $validated['semester'],
                        $validated['generalAverage'] ?? null,
                        $docLabel,
                        $uploadedFile->getClientOriginalName(),
                        $path
                    );
                }
            }
        }

        // Deduplicate history by schoolYear + semester + documentType keeping newest
        $dedupedHistory = [];
        $seenKeys = [];

        for ($i = count($history) - 1; $i >= 0; $i--) {
            $item = $history[$i];
            $sy = strtolower(trim($item['schoolYear'] ?? $item['school_year'] ?? ''));
            $sem = strtolower(trim($item['semester'] ?? ''));
            $doc = strtolower(trim($item['documentType'] ?? $item['document_type'] ?? ''));
            $key = "{$sy}|{$sem}|{$doc}";

            if (! isset($seenKeys[$key])) {
                $seenKeys[$key] = true;
                array_unshift($dedupedHistory, $item);
            }
        }

        $scholar->update(['requirements_history' => $dedupedHistory]);

        return response()->json([
            'message' => 'Requirements submitted successfully.',
            'requirements_history' => $dedupedHistory,
        ]);
    }

    /**
     * Reupload a specific application requirement document marked for revision.
     */
    public function reuploadApplicationDocument(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
            'file' => 'required|file',
        ]);

        $application = EcesproApplication::where('user_id', $request->user()->id)
            ->latest()
            ->firstOrFail();

        $requirements = $application->submitted_requirements ?? [];
        $found = false;

        foreach ($requirements as &$req) {
            if ($req['id'] == $validated['document_id']) {
                $path = $request->file('file')->store('ecespro/applications', 'public');
                $req['path'] = $path;
                $req['status'] = 'Pending';
                $req['remarks'] = '';
                $found = true;
                break;
            }
        }

        if (! $found) {
            return response()->json(['message' => 'Document not found in application.'], 404);
        }

        $application->update([
            'submitted_requirements' => $requirements,
            'application_status' => 'Under Review',
        ]);

        return response()->json([
            'message' => 'Document reuploaded successfully and submitted for re-validation.',
            'application' => $application->load(['examination.batch', 'interview.batch', 'contract']),
        ]);
    }
}
