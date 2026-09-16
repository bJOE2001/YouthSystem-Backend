<?php
$file = 'C:/SYSTEMS/CICTMO/YouthSystem-Backend/app/Http/Controllers/EcesproExamBatchController.php';
$content = file_get_contents($file);

$newMethods = <<<EOT
    public function getEssayQuestions(\)
    {
        \ = EcesproExamBatch::with(['examinations.answers' => function(\) {
            \->whereHas('question', function(\) {
                \->where('type', 'essay');
            })->whereNull('awarded_points');
        }])->findOrFail(\);

        \ = collect();

        foreach (\->examinations as \) {
            foreach (\->answers as \) {
                \ = \->question_id;
                if (!\->has(\)) {
                    \->put(\, [
                        'question' => \->question,
                        'pending_count' => 0
                    ]);
                }
                
                \ = \->get(\);
                \['pending_count']++;
                \->put(\, \);
            }
        }

        return response()->json(array_values(\->toArray()));
    }

    public function getPendingAnswersForQuestion(\, \)
    {
        \ = EcesproExamBatch::with(['examinations' => function(\) use (\) {
            \->with(['application.user.youthProfile', 'answers' => function(\) use (\) {
                \->where('question_id', \)->whereNull('awarded_points');
            }]);
        }])->findOrFail(\);

        \ = [];
        foreach (\->examinations as \) {
            foreach (\->answers as \) {
                \[] = [
                    'examination_id' => \->id,
                    'answer_id' => \->id,
                    'applicant_name' => \->application->user->youthProfile->first_name . ' ' . \->application->user->youthProfile->last_name,
                    'application_no' => 'APP-' . str_pad(\->application->id, 4, '0', STR_PAD_LEFT),
                    'profile_picture' => \->application->user->youthProfile->profile_picture,
                    'answer_text' => \->answer_text,
                    'question_points' => \->question->points ?? 10
                ];
            }
        }

        return response()->json(\);
    }

    public function gradeSingleAnswer(Request \, \, \)
    {
        \->validate([
            'awarded_points' => 'required|numeric|min:0'
        ]);

        \ = \App\Models\EcesproApplicantExamAnswer::with('examination')->findOrFail(\);
        
        if (\->awarded_points !== null) {
            return response()->json(['message' => 'Answer is already graded'], 400);
        }

        \->awarded_points = \->awarded_points;
        \->is_correct = \->awarded_points > 0;
        \->save();

        // Increment exam score
        \ = \->examination;
        \->score = (\->score ?? 0) + \->awarded_points;
        \->save();

        // Check if there are any pending essays left for this examination
        \ = \App\Models\EcesproApplicantExamAnswer::where('ecespro_examination_id', \->id)
            ->whereHas('question', function(\) {
                \->where('type', 'essay');
            })
            ->whereNull('awarded_points')
            ->count();

        if (\ === 0) {
            // Evaluated pass/fail
            \ = \->answers()->count(); // simplistic way, better to sum points or just use passing_score
            // Or get passing score from setup
            \ = \App\Models\EcesproExaminationSetup::where('batch_id', \)->first();
            \ = \ ? \->passing_score : 50; 
            
            // Wait, we need to check if score >= passingScore
            \->status = \->score >= \ ? 'Passed' : 'Failed';
            \->save();
        }

        return response()->json(['message' => 'Answer graded successfully', 'remaining_pending' => \, 'new_status' => \->status]);
    }
EOT;

// Find the position of getPendingEssays
\ = strpos(\, 'public function getPendingEssays');
\ = strpos(\, 'public function delete(', \);

if (\ !== false && \ !== false) {
    \ = substr(\, 0, \) . \ . "\n\n    " . substr(\, \);
    file_put_contents(\, \);
    echo "Replaced successfully.";
} else {
    echo "Could not find target methods.";
}
?>
