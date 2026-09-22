<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EcesproExaminationSetup;
use Illuminate\Http\Request;

class EcesproExaminationSetupController extends Controller
{
    public function showByProgram($program_id)
    {
        $setup = EcesproExaminationSetup::with('questionnaire')->where('ecespro_program_id', $program_id)->first();
        if (!$setup) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($setup);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ecespro_program_id' => 'required|exists:ecespro_programs,id',
            'questionnaire_id' => 'required|exists:ecespro_exam_questionnaires,id',
            'passing_percentage' => 'numeric|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'time_limit_minutes' => 'integer|min:1',
        ]);

        $setup = EcesproExaminationSetup::updateOrCreate(
            ['ecespro_program_id' => $request->ecespro_program_id],
            $request->only('questionnaire_id', 'passing_percentage', 'shuffle_questions', 'time_limit_minutes')
        );

        // Recalculate statuses for completed exams based on the new passing percentage
        $recalculableStatuses = ['For Examination', 'Failed Exam', 'Qualified for Interview'];

        $completedExams = \App\Models\EcesproExamination::with('application')
            ->whereHas('application', function ($q) use ($setup, $recalculableStatuses) {
                $q->where('ecespro_program_id', $setup->ecespro_program_id)
                    ->whereIn('application_status', $recalculableStatuses);
            })
            ->whereNotNull('completed_at')
            ->get();

        // Look up total possible points from the questionnaire for fallback (raw numeric scores)
        $totalPossiblePoints = \App\Models\EcesproExamQuestion::where('questionnaire_id', $setup->questionnaire_id)->sum('points');

        $recalculatedCount = 0;
        $changedCount = 0;
        $transitions = [];

        foreach ($completedExams as $exam) {
            if ($exam->score === null) continue;

            $earnedPoints = null;
            $totalPoints = null;

            // Handle slash format: "15/20"
            $scoreParts = explode('/', $exam->score);
            if (count($scoreParts) == 2) {
                $earnedPoints = (float) $scoreParts[0];
                $totalPoints = (float) $scoreParts[1];
            } else {
                // Handle raw numeric format: "15"
                $earnedPoints = (float) $exam->score;
                $totalPoints = $totalPossiblePoints > 0 ? (float) $totalPossiblePoints : null;

                // Convert to slash format for consistency going forward
                if ($totalPoints !== null) {
                    $exam->score = $earnedPoints . '/' . $totalPoints;
                }
            }

            if ($totalPoints === null || $totalPoints <= 0) continue;

            $percentage = ($earnedPoints / $totalPoints) * 100;
            $passed = $percentage >= $setup->passing_percentage;
            $newStatus = $passed ? 'Passed' : 'Failed';

            $recalculatedCount++;

            if ($exam->status !== $newStatus) {
                $oldStatus = $exam->status;
                $exam->status = $newStatus;
                $exam->save();

                // Update application_status
                $newAppStatus = $newStatus === 'Passed' ? 'Qualified for Interview' : 'Failed Exam';
                $exam->application()->update([
                    'application_status' => $newAppStatus,
                ]);

                $changedCount++;
                $transitionKey = ($oldStatus ?? 'Unknown') . ' → ' . $newStatus;
                $transitions[$transitionKey] = ($transitions[$transitionKey] ?? 0) + 1;
            } else {
                // Still save if we converted the score format
                $exam->save();
            }
        }

        return response()->json([
            'setup' => $setup,
            'recalculation' => [
                'total_recalculated' => $recalculatedCount,
                'statuses_changed' => $changedCount,
                'transitions' => $transitions,
            ],
        ]);
    }
    public function markAsDone($id)
    {
        $setup = \App\Models\EcesproExaminationSetup::with('program')->findOrFail($id);

        $incompleteExamsCount = \App\Models\EcesproExamination::whereHas('application', function ($query) use ($setup) {
            $query->where('ecespro_program_id', $setup->ecespro_program_id);
        })->whereNull('completed_at')->count();

        if ($incompleteExamsCount > 0) {
            return response()->json(['message' => 'Cannot mark as done. ' . $incompleteExamsCount . ' applicant(s) have not finished their exam.'], 400);
        }

        $ungradedEssaysCount = \App\Models\EcesproApplicantExamAnswer::whereHas('question', function ($query) {
            $query->where('type', 'essay');
        })->whereHas('examination.application', function ($query) use ($setup) {
            $query->where('ecespro_program_id', $setup->ecespro_program_id);
        })->whereNull('awarded_points')->count();

        if ($ungradedEssaysCount > 0) {
            return response()->json(['message' => 'Cannot mark as done. ' . $ungradedEssaysCount . ' essay answer(s) are pending for grading.'], 400);
        }

        if ($setup->program) {
            $setup->program->update(['status' => 'Exam Completed']);
        }
        return response()->json(['message' => 'Exam marked as done successfully.']);
    }

    public function destroy($id)
    {
        $setup = \App\Models\EcesproExaminationSetup::findOrFail($id);
        
        $hasEnabledBatch = \App\Models\EcesproExamBatch::where('is_exam_enabled', true)
            ->whereHas('examinations', function ($q) use ($setup) {
                $q->whereHas('application', function ($appQ) use ($setup) {
                    $appQ->where('ecespro_program_id', $setup->ecespro_program_id);
                });
            })->exists();
            
        if ($hasEnabledBatch) {
            return response()->json(['message' => 'Cannot remove setup. The examination is currently enabled for a batch under this program. Please close the online exam first.'], 400);
        }
        
        $hasStartedExam = \App\Models\EcesproExamination::whereHas('application', function ($q) use ($setup) {
            $q->where('ecespro_program_id', $setup->ecespro_program_id);
        })->whereNotNull('started_at')->exists();
        
        if ($hasStartedExam) {
            return response()->json(['message' => 'Cannot remove setup. Applicants have already started or completed their exams.'], 400);
        }

        $setup->delete();
        return response()->json(['message' => 'Examination setup removed successfully']);
    }
}
