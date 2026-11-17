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
        'allow_multiple_answers' => 'boolean',
    ];

    protected $appends = [
        'image_url',
        'image_base64',
    ];

    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }
        return asset('storage/' . $this->image_path);
    }

    public function getImageBase64Attribute()
    {
        if (!$this->image_path) {
            return null;
        }
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->image_path)) {
                $file = \Illuminate\Support\Facades\Storage::disk('public')->get($this->image_path);
                $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($this->image_path) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($file);
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    public function questionnaire()
    {
        return $this->belongsTo(EcesproExamQuestionnaire::class, 'questionnaire_id');
    }

    public function choices()
    {
        return $this->hasMany(EcesproExamAnswerChoice::class, 'question_id');
    }
}



