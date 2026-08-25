<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'school_year',
        'start_date',
        'end_date',
        'status',
        'description',
        // 'scholarship_benefits',
        // 'program_eligibility',
        'application_requirements',
    ];

    protected function casts(): array
    {
        return [
            // 'scholarship_benefits' => 'array',
            // 'program_eligibility' => 'array',
            'application_requirements' => 'array',
        ];
    }

    public function applications()
    {
        return $this->hasMany(EcesproApplication::class);
    }
}
