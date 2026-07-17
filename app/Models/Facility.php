<?php

namespace App\Models;

use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'location',
        'available_time',
        'status',
        'image',
    ];

    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class);
    }
}
