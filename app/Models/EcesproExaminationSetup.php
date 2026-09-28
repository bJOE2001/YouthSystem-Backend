<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproExaminationSetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecespro_program_id',
        'questionnaire_id',
        'passing_percentage',
        'shuffle_questions',
        'time_limit_minutes',
        'attempts_allowed'
    ];

    protected $casts = [
        'shuffle_questions' => 'boolean',
        'passing_percentage' => 'float',
        'time_limit_minutes',
        'attempts_allowed' => 'integer',
    ];

    public function program()
    {
        return $this->belongsTo(EcesproProgram::class, 'ecespro_program_id');
    }

    public function questionnaire()
    {
        return $this->belongsTo(EcesproExamQuestionnaire::class, 'questionnaire_id');
    }
}