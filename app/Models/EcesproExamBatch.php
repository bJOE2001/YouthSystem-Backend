<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproExamBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_name',
        'exam_date',
        'time',
        'venue',
        'status',
    ];

    public function examinations()
    {
        return $this->hasMany(EcesproExamination::class);
    }
}
