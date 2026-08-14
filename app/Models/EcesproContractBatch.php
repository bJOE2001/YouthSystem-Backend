<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproContractBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_name',
        'signing_date',
        'time',
        'venue',
        'status',
    ];

    public function contracts()
    {
        return $this->hasMany(EcesproContract::class);
    }
}
