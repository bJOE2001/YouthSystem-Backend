<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproGrantRelease extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'scholar_id',
        'status',
    ];

    public function batch()
    {
        return $this->belongsTo(EcesproGrantReleaseBatch::class, 'batch_id');
    }

    public function scholar()
    {
        return $this->belongsTo(EcesproScholar::class, 'scholar_id');
    }
}
