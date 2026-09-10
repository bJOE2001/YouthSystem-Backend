<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcesproScholar extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ecespro_application_id',
        'scholar_no',
        'school',
        'course',
        'compliance_status',
        'requirements_history',
        'status',
        'remarks',
        'allowance_received_amount',
        'required_volunteer_hours',
        'total_rendered_hours',
        'is_volunteer_completed',
        'scholar_position_id',
    ];

    protected $appends = [
        'effective_required_volunteer_hours',
        'remaining_hours',
        'progress_percentage',
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'year_level',
        'position_name',
    ];

    protected function casts(): array
    {
        return [
            'requirements_history' => 'array',
            'allowance_received_amount' => 'decimal:2',
            'required_volunteer_hours' => 'decimal:2',
            'total_rendered_hours' => 'decimal:2',
            'is_volunteer_completed' => 'boolean',
        ];
    }

    public function scholarPosition(): BelongsTo
    {
        return $this->belongsTo(ScholarPosition::class, 'scholar_position_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(EcesproApplication::class, 'ecespro_application_id');
    }

    public function volunteerLogs(): HasMany
    {
        return $this->hasMany(EcesproVolunteerLog::class, 'scholar_id');
    }

    public function getEffectiveRequiredVolunteerHoursAttribute(): float
    {
        return (float) ($this->required_volunteer_hours ?: EcesproSetting::get('required_volunteer_hours', 36.00));
    }

    public function getRemainingHoursAttribute(): float
    {
        return max(0, round($this->effective_required_volunteer_hours - (float) ($this->total_rendered_hours ?: 0.00), 2));
    }

    public function getProgressPercentageAttribute(): float
    {
        $effectiveRequired = $this->effective_required_volunteer_hours;

        if ($effectiveRequired <= 0) {
            return 100.0;
        }

        return min(100.0, round(((float) ($this->total_rendered_hours ?: 0.00) / $effectiveRequired) * 100, 1));
    }

    public function getFirstNameAttribute()
    {
        if ($this->relationLoaded('user') && $this->user && $this->user->relationLoaded('youthProfile') && $this->user->youthProfile) {
            return $this->user->youthProfile->first_name;
        }
        if ($this->relationLoaded('application') && $this->application) {
            return $this->application->first_name;
        }

        return $this->attributes['first_name'] ?? null;
    }

    public function getMiddleNameAttribute()
    {
        if ($this->relationLoaded('user') && $this->user && $this->user->relationLoaded('youthProfile') && $this->user->youthProfile) {
            return $this->user->youthProfile->middle_name;
        }
        if ($this->relationLoaded('application') && $this->application) {
            return $this->application->middle_name;
        }

        return $this->attributes['middle_name'] ?? null;
    }

    public function getLastNameAttribute()
    {
        if ($this->relationLoaded('user') && $this->user && $this->user->relationLoaded('youthProfile') && $this->user->youthProfile) {
            return $this->user->youthProfile->last_name;
        }
        if ($this->relationLoaded('application') && $this->application) {
            return $this->application->last_name;
        }

        return $this->attributes['last_name'] ?? null;
    }

    public function getYearLevelAttribute()
    {
        if ($this->relationLoaded('application') && $this->application) {
            return $this->application->year_level ?: $this->application->previous_grade_college_year_level;
        }

        return $this->attributes['year_level'] ?? null;
    }

    public function getCourseAttribute($value)
    {
        if (empty($value) && $this->relationLoaded('application') && $this->application) {
            return $this->application->course;
        }

        return $value;
    }

    public function getSchoolAttribute($value)
    {
        if (empty($value) && $this->relationLoaded('application') && $this->application) {
            return $this->application->school;
        }

        return $value;
    }

    /**
     * Get the standardized full name of the scholar.
     */
    public function getFullNameAttribute()
    {
        if ($this->relationLoaded('user') && $this->user && $this->user->relationLoaded('youthProfile') && $this->user->youthProfile) {
            $profile = $this->user->youthProfile;
            $middle = $profile->middle_name ? ' '.$profile->middle_name.' ' : ' ';

            return trim(($profile->first_name ?? '').$middle.($profile->last_name ?? ''));
        }

        if ($this->relationLoaded('application') && $this->application) {
            $middle = $this->application->middle_name ? ' '.$this->application->middle_name.' ' : ' ';

            return trim(($this->application->first_name ?? '').$middle.($this->application->last_name ?? ''));
        }

        if (isset($this->attributes['first_name']) || isset($this->attributes['last_name'])) {
            $middle = $this->attributes['middle_name'] ?? '';
            $middleSpacing = $middle ? ' '.$middle.' ' : ' ';

            return trim(($this->attributes['first_name'] ?? '').$middleSpacing.($this->attributes['last_name'] ?? ''));
        }

        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->name;
        }

        return $this->attributes['name'] ?? 'N/A';
    }

    /**
     * Recalculate total rendered hours and update volunteer completion status.
     */
    public function recalculateVolunteerHours(?string $semesterPeriod = null): void
    {
        $query = $this->volunteerLogs();
        if ($semesterPeriod !== null && $semesterPeriod !== '') {
            $query->where('semester_period', $semesterPeriod);
        }

        $totalRendered = (float) $query->sum('hours_rendered');
        $required = (float) ($this->required_volunteer_hours ?: EcesproSetting::get('required_volunteer_hours', 36.00));

        $this->total_rendered_hours = $totalRendered;
        $this->is_volunteer_completed = $totalRendered >= $required;
        $this->save();
    }

    public function getPositionNameAttribute(): ?string
    {
        if ($this->relationLoaded('scholarPosition') && $this->scholarPosition) {
            return $this->scholarPosition->name;
        }

        return null;
    }
}
