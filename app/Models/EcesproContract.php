<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecespro_application_id',
        'ecespro_contract_batch_id',
        'schedule',
        'guardian',
        'documents_status',
        'status',
    ];

    public function application()
    {
        return $this->belongsTo(EcesproApplication::class, 'ecespro_application_id');
    }

    public function batch()
    {
        return $this->belongsTo(EcesproContractBatch::class, 'ecespro_contract_batch_id');
    }
}
