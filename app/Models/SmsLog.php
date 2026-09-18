<?php

namespace App\Models;

use Database\Factories\SmsLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    /** @use HasFactory<SmsLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'recipient',
        'message',
        'status',
        'response_code',
        'response_body',
        'event_type',
    ];

    /**
     * Get the user that owns the SMS log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
