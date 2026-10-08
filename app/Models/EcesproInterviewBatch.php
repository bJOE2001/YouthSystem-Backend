<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproInterviewBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_name',
        'interview_date',
        'time',

        'mode',
        'status',
    ];

    public function interviews()
    {
        return $this->hasMany(EcesproInterview::class);
    }
}
