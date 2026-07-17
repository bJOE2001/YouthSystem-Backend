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
        'slots',
        'status',
        'remarks',
    ];

    public function applications()
    {
        return $this->hasMany(EcesproApplication::class);
    }
}
