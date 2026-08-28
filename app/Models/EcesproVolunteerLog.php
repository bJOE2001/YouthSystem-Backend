<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcesproVolunteerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'scholar_id',
        'event_id',
        'activity_type',
        'duty_title',
        'time_in',
        'time_out',
        'hours_rendered',
        'semester_period',
        'verified_by_user_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'hours_rendered' => 'decimal:2',
        ];
    }

    public function scholar(): BelongsTo
    {
        return $this->belongsTo(EcesproScholar::class, 'scholar_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
