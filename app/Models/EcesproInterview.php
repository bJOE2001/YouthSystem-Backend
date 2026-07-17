<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproInterview extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecespro_application_id',
        'ecespro_interview_batch_id',
        'remarks',
        'status',
    ];

    public function application()
    {
        return $this->belongsTo(EcesproApplication::class, 'ecespro_application_id');
    }

    public function batch()
    {
        return $this->belongsTo(EcesproInterviewBatch::class, 'ecespro_interview_batch_id');
    }
}
