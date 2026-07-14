<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'aip_reference_code',
        'ppa_classification',
        'center_of_participation',
        'sustainable_development_goal',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'has_no_allocated_budget',
        'no_budget_reason',
        'budget_allocated',
        'budget_utilized',
        'performance_indicator',
        'primary_objective_1',
        'primary_objective_2',
        'primary_objective_3',
        'status',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'has_no_allocated_budget' => 'boolean',
            'budget_allocated' => 'decimal:2',
            'budget_utilized' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_user')->withPivot('attended_at')->withTimestamps();
    }
}
