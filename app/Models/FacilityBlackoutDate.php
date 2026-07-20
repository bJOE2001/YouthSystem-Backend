<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityBlackoutDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'date',
        'reason',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
