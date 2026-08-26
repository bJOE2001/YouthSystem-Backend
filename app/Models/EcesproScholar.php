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
        'allowance_received_amount',
        'required_volunteer_hours',
        'total_rendered_hours',
        'is_volunteer_completed',
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

    /**
     * Recalculate total rendered hours and update volunteer completion status.
     */
    public function recalculateVolunteerHours(): void
    {
        $totalRendered = (float) $this->volunteerLogs()->sum('hours_rendered');
        $required = (float) ($this->required_volunteer_hours ?: 36.00);

        $this->total_rendered_hours = $totalRendered;
        $this->is_volunteer_completed = $totalRendered >= $required;
        $this->save();
    }
}
