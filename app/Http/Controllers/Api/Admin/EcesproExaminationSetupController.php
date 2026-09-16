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

        return response()->json($setup);
    }
    public function markAsDone($id)
    {
        $setup = \App\Models\EcesproExaminationSetup::with('program')->findOrFail($id);
        if ($setup->program) {
            $setup->program->update(['status' => 'Exam Completed']);
        }
        return response()->json(['message' => 'Exam marked as done successfully.']);
    }
}
