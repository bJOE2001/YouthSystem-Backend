<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproComplianceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'school_year',
        'semester',
        'start_date',
        'end_date',
        'status',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
