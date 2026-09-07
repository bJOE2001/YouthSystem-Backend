<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproGrantReleaseBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_name',
        'release_date',
        'time',
        'venue',
        'status',
        'notify_scholars',
    ];

    protected $casts = [
        'release_date' => 'date',
        'notify_scholars' => 'boolean',
    ];

    public function grants()
    {
        return $this->hasMany(EcesproGrantRelease::class, 'batch_id');
    }
}
