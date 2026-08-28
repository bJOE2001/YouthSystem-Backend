<?php

namespace App\Models;

use Database\Factories\SkOfficialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class SkOfficial extends Model
{
    /** @use HasFactory<SkOfficialFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'initials',
        'barangay',
        'contact',
        'email',
        'committee',
        'position',
        'responsibilities',
        'term',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function youthProfile(): HasOneThrough
    {
        return $this->hasOneThrough(
            YouthProfile::class,
            User::class,
            'id',          // Foreign key on users table (users.id)
            'user_id',     // Foreign key on youth_profiles table (youth_profiles.user_id)
            'user_id',     // Local key on sk_officials table (sk_officials.user_id)
            'id'           // Local key on users table (users.id)
        );
    }
}
