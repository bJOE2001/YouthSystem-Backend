<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackQuestionnaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'target',
        'barangay',
        'scope',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected $appends = [
        'times_assigned',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FeedbackQuestion::class, 'questionnaire_id')->orderBy('sort_order', 'asc');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class, 'questionnaire_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'feedback_questionnaire_id');
    }

    public function sportsPrograms(): HasMany
    {
        return $this->hasMany(SportsProgram::class, 'feedback_questionnaire_id');
    }

    /**
     * Compute how many times this questionnaire template has been assigned to activities.
     */
    public function getTimesAssignedAttribute(): int
    {
        return (int) ($this->events()->count() + $this->sportsPrograms()->count());
    }
}

