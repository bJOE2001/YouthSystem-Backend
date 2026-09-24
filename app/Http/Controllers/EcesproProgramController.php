<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\EcesproProgram;
use App\Models\User;
use App\Notifications\NewEcesproProgramNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EcesproProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $programs = EcesproProgram::with('examinationSetup.questionnaire')
            ->withCount('applications')
            ->withCount(['applications as passed_count' => function ($query) {
                $query->whereHas('examination', function ($q) {
                    $q->where('status', 'Passed');
                });
            }])
            ->withCount(['applications as failed_count' => function ($query) {
                $query->whereHas('examination', function ($q) {
                    $q->where('status', 'Failed');
                });
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($programs as $program) {
            $batchesCount = \App\Models\EcesproExamBatch::whereHas('examinations.application', function($q) use ($program) {
                $q->where('ecespro_program_id', $program->id);
            })->count();

            $has_completed_batch = \App\Models\EcesproExamBatch::where('status', 'Exam Completed')
                ->whereHas('examinations.application', function($q) use ($program) {
                    $q->where('ecespro_program_id', $program->id);
                })->exists();

            $has_started_exam = \App\Models\EcesproExamination::whereHas('application', function($q) use ($program) {
                $q->where('ecespro_program_id', $program->id);
            })->whereNotNull('started_at')->exists();

            $incompleteBatchesCount = \App\Models\EcesproExamBatch::where('status', '!=', 'Exam Completed')
                ->whereHas('examinations.application', function($q) use ($program) {
                    $q->where('ecespro_program_id', $program->id);
                })->count();

            $all_exam_batches_completed = ($batchesCount > 0 && $incompleteBatchesCount === 0);

            $program->setAttribute('has_completed_batch', $has_completed_batch);
            $program->setAttribute('batch_count', $batchesCount);
            $program->setAttribute('has_started_exam', $has_started_exam);
            $program->setAttribute('all_exam_batches_completed', $all_exam_batches_completed);
        }

        return $programs;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'school_year' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            // 'slots' => 'required|integer|min:1',
            'status' => 'required|string',
            'description' => 'nullable|string',
            // 'benefits' => 'nullable|array',
            // 'benefits.*' => 'string',
            // 'eligibility' => 'nullable|array',
            // 'eligibility.*' => 'string',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string',
        ]);

        $data = $validated;
        // if (array_key_exists('benefits', $data)) {
        //     $data['scholarship_benefits'] = $data['benefits'];
        //     unset($data['benefits']);
        // }
        // if (array_key_exists('eligibility', $data)) {
        //     $data['program_eligibility'] = $data['eligibility'];
        //     unset($data['eligibility']);
        // }
        if (array_key_exists('requirements', $data)) {
            $data['application_requirements'] = $data['requirements'];
            unset($data['requirements']);
        }

        $program = EcesproProgram::create($data);

        if (isset($data['status']) && strcasecmp($data['status'], 'Open') === 0) {
            EcesproProgram::where('id', '!=', $program->id)
                ->where('status', 'Open')
                ->update(['status' => 'Closed']);

            $recipients = User::where(function ($query) {
                $query->whereIn('role', [
                    UserRole::Youth->value,
                    UserRole::SkAdmin->value,
                    'youth',
                    'sk_admin',
                ]);
            })->get();

            Notification::send($recipients, new NewEcesproProgramNotification($program));
        }

        return response()->json($program, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproProgram $ecesproProgram)
    {
        return $ecesproProgram;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproProgram $ecesproProgram)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'school_year' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            // 'slots' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string',
            'description' => 'nullable|string',
            // 'benefits' => 'nullable|array',
            // 'benefits.*' => 'string',
            // 'eligibility' => 'nullable|array',
            // 'eligibility.*' => 'string',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string',
        ]);

        $data = $validated;
        // if (array_key_exists('benefits', $data)) {
        //     $data['scholarship_benefits'] = $data['benefits'];
        //     unset($data['benefits']);
        // }
        // if (array_key_exists('eligibility', $data)) {
        //     $data['program_eligibility'] = $data['eligibility'];
        //     unset($data['eligibility']);
        // }
        if (array_key_exists('requirements', $data)) {
            $data['application_requirements'] = $data['requirements'];
            unset($data['requirements']);
        }

        $previousStatus = $ecesproProgram->status;
        $ecesproProgram->update($data);

        if (isset($data['status']) && strcasecmp($data['status'], 'Open') === 0) {
            EcesproProgram::where('id', '!=', $ecesproProgram->id)
                ->where('status', 'Open')
                ->update(['status' => 'Closed']);

            if (strcasecmp($previousStatus, 'Open') !== 0) {
                $recipients = User::where(function ($query) {
                    $query->whereIn('role', [
                        UserRole::Youth->value,
                        UserRole::SkAdmin->value,
                        'youth',
                        'sk_admin',
                    ]);
                })->get();

                Notification::send($recipients, new NewEcesproProgramNotification($ecesproProgram));
            }
        }

        return $ecesproProgram;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproProgram $ecesproProgram)
    {
        $ecesproProgram->delete();

        return response()->noContent();
    }
}
