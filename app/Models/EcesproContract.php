<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecespro_application_id',
        'schedule',
        'guardian',
        'documents_status',
        'status',
    ];

    public function application()
    {
        return $this->belongsTo(EcesproApplication::class, 'ecespro_application_id');
    }
}
