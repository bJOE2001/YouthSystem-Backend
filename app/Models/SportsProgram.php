<?php

namespace App\Models;

use Database\Factories\SportsProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'budget_allocated',
        'budget_utilized',
        'objective_1',
        'objective_2',
        'objective_3',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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
        return $this->belongsToMany(User::class)->withPivot('attended_at')->withTimestamps();
    }
}
