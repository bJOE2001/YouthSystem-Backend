<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproExamination extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecespro_application_id',
        'ecespro_exam_batch_id',
        'score',
        'status',
        'started_at',
        'completed_at',
        'attempts_used',
        'time_extension_minutes'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_extension_minutes' => 'integer'
    ];

    public function application()
    {
        return $this->belongsTo(EcesproApplication::class, 'ecespro_application_id');
    }

    public function batch()
    {
        return $this->belongsTo(EcesproExamBatch::class, 'ecespro_exam_batch_id');
    }

    public function answers()
    {
        return $this->hasMany(EcesproApplicantExamAnswer::class, 'ecespro_examination_id');
    }
}
