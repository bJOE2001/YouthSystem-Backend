<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproExamAnswerChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'choice_text',
        'is_correct'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(EcesproExamQuestion::class, 'question_id');
    }
}