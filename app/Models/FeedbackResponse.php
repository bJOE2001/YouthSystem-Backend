<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'user_id',
        'event_id',
        'sports_program_id',
        'activity_type',
        'activity_name',
        'target',
        'barangay',
        'is_anonymous',
        'submitted_by_name',
        'user_name',
        'user_email',
        'average_rating',
        'general_remarks',
        'answers',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'is_anonymous' => 'boolean',
            'average_rating' => 'float',
        ];
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(FeedbackQuestionnaire::class, 'questionnaire_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sportsProgram(): BelongsTo
    {
        return $this->belongsTo(SportsProgram::class);
    }
}

