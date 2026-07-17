<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproScholar extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ecespro_application_id',
        'scholar_no',
        'school',
        'course',
        'compliance_status',
        'requirements_history',
        'status',
        'allowance_received_amount',
    ];

    protected $casts = [
        'requirements_history' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function application()
    {
        return $this->belongsTo(EcesproApplication::class, 'ecespro_application_id');
    }
}
