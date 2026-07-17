<?php

namespace App\Http\Controllers;

use App\Models\EcesproApplication;
use Illuminate\Http\Request;

class EcesproApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = EcesproApplication::with(['user', 'program', 'examination', 'interview', 'contract']);

        if ($request->has('status')) {
            $query->where('application_status', $request->status);
        }

        if ($request->has('program_id')) {
            $query->where('ecespro_program_id', $request->program_id);
        }

        return response()->json($query->latest()->paginate($request->input('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'ecespro_program_id' => 'required|exists:ecespro_programs,id',

            // Personal Info
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:50',
            'gender' => 'nullable|string|max:50',
            'birthdate' => 'nullable|string|max:255',
            'age' => 'nullable|string|max:50',
            'place_of_birth' => 'nullable|string|max:255',
            'sex' => 'nullable|string|max:50',
            'civil_status' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:100',
            'personal_zip_code' => 'nullable|string|max:50',
            'ip_or_muslim' => 'nullable|string|max:50',
            'type_of_disability' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:50',
            'email_address' => 'nullable|string|email|max:255',
            'permanent_mailing_address' => 'nullable|string|max:1000',

            // Educational Info
            'previous_grade_college_year_level' => 'nullable|string|max:255',
            'general_average' => 'nullable|string|max:50',
            'school_attended_to_enroll' => 'nullable|string|max:255',
            'school_address' => 'nullable|string|max:1000',
            'course_intended_to_enroll' => 'nullable|string|max:255',
            'type_of_school' => 'nullable|string|max:100',
            'school_year' => 'nullable|string|max:50',
            'school_citizenship' => 'nullable|string|max:100',
            'school' => 'nullable|string|max:255',
            'year_level' => 'nullable|string|max:100',
            'course' => 'nullable|string|max:255',
            'school_zip_code' => 'nullable|string|max:50',

            // Father's Info
            'father_last_name' => 'nullable|string|max:255',
            'father_first_name' => 'nullable|string|max:255',
            'father_address' => 'nullable|string|max:1000',
            'father_occupation' => 'nullable|string|max:255',
            'father_educational_attainment' => 'nullable|string|max:255',

            // Mother's Info
            'mother_maiden_middle_name' => 'nullable|string|max:255',
            'mother_maiden_last_name' => 'nullable|string|max:255',
            'mother_occupation' => 'nullable|string|max:255',
            'mother_educational_attainment' => 'nullable|string|max:255',

            // Guardian's Info
            'guardian_maiden_middle_name' => 'nullable|string|max:255',
            'guardian_maiden_last_name' => 'nullable|string|max:255',
            'guardian_occupation' => 'nullable|string|max:255',
            'guardian_educational_attainment' => 'nullable|string|max:255',

            // Other Family Info
            'parents_guardian_total_income' => 'nullable|string|max:255',
            'number_of_siblings_in_family' => 'nullable|string|max:50',
            'parents_marital_status' => 'nullable|string|max:100',

            // Requirements (Can be URLs or simple strings for now since frontend emits strings)
            'certificate_of_indigency' => 'nullable|string|max:1000',
            'report_card_grades' => 'nullable|string|max:1000',
            'certificate_of_enrollment' => 'nullable|string|max:1000',
            'certificate_of_registration' => 'nullable|string|max:1000',
            'good_moral_certificate' => 'nullable|string|max:1000',
            'barangay_clearance' => 'nullable|string|max:1000',
            'other_supporting_documents' => 'nullable|string|max:1000',
        ]);

        $application = EcesproApplication::create($validated);

        return response()->json($application->load('user', 'program'), 201);
    }

    public function show(EcesproApplication $ecesproApplication)
    {
        return response()->json($ecesproApplication->load(['user', 'program', 'examination', 'interview', 'contract']));
    }

    public function update(Request $request, EcesproApplication $ecesproApplication)
    {
        $validated = $request->validate([
            'application_status' => 'sometimes|required|string|in:Pending,Under Review,Exam Scheduled,Interview Scheduled,Contract Scheduled,Approved,Rejected',
        ]);

        $updateData = $request->except(['application_status', 'user_id', 'ecespro_program_id']);
        if ($request->has('application_status')) {
            $updateData['application_status'] = $request->application_status;
        }

        $ecesproApplication->update($updateData);

        return response()->json($ecesproApplication->load('user', 'program'));
    }

    public function destroy(EcesproApplication $ecesproApplication)
    {
        $ecesproApplication->delete();

        return response()->json(null, 204);
    }
}
