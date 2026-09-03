<?php

namespace App\Models;

use Database\Factories\SportsProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportsProgram extends Model
{
    /** @use HasFactory<SportsProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'strategic_direction',
        'start_date',
        'end_date',
        'start_time',
        'location',
        'objective_1',
        'objective_2',
        'objective_3',
        'status',
        'certificate_template_path',
        'certificate_settings',
        'open_to_all_barangays',
        'barangay',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'open_to_all_barangays' => 'boolean',
            'certificate_settings' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'sports_program_user')
            ->withPivot(['id', 'attended_at', 'team_name', 'teammates'])
            ->withTimestamps();
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}