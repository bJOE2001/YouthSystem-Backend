<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproApplicantExamAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecespro_examination_id',
        'question_id',
        'answer_choice_id',
        'answer_text',
        'is_correct',
        'awarded_points'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'awarded_points' => 'integer'
    ];

    public function examination()
    {
        return $this->belongsTo(EcesproExamination::class, 'ecespro_examination_id');
    }

    public function question()
    {
        return $this->belongsTo(EcesproExamQuestion::class, 'question_id');
    }

    public function choice()
    {
        return $this->belongsTo(EcesproExamAnswerChoice::class, 'answer_choice_id');
    }
}

