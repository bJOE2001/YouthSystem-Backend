<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'type',
        'question_text',
        'points',
        'image_path',
        'allow_multiple_answers',
        'correct_answer_text'
    ];

    protected $casts = [
        'allow_multiple_answers',
        'correct_answer_text' => 'boolean',
    ];

    public function questionnaire()
    {
        return $this->belongsTo(EcesproExamQuestionnaire::class, 'questionnaire_id');
    }

    public function choices()
    {
        return $this->hasMany(EcesproExamAnswerChoice::class, 'question_id');
    }
}


