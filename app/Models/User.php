<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if (Schema::hasTable('sports_program_user')) {
                DB::table('sports_program_user')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('event_user')) {
                DB::table('event_user')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('ecespro_scholars')) {
                DB::table('ecespro_scholars')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('feedbacks')) {
                DB::table('feedbacks')->where('user_id', $user->id)->update(['user_id' => null]);
            }

            if (Schema::hasTable('announcement_user')) {
                DB::table('announcement_user')->where('user_id', $user->id)->delete();
            }

            if (! empty($user->email)) {
                if (Schema::hasTable('sk_officials')) {
                    DB::table('sk_officials')->where('email', $user->email)->delete();
                }

                if (Schema::hasTable('lydc_members')) {
                    DB::table('lydc_members')->where('email', $user->email)->delete();
                }
            }
        });
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function youthProfile(): HasOne
    {
        return $this->hasOne(YouthProfile::class);
    }

    public function ecesproScholar(): HasOne
    {
        return $this->hasOne(EcesproScholar::class)->latest();
    }

    public function joinedEvents()
    {
        return $this->belongsToMany(Event::class)->withPivot('attended_at')->withTimestamps();
    }

    public function joinedSportsPrograms()
    {
        return $this->belongsToMany(SportsProgram::class, 'sports_program_user')->withPivot(['id', 'attended_at', 'team_name', 'teammates'])->withTimestamps();
    }

    public function readAnnouncements()
    {
        return $this->belongsToMany(Announcement::class, 'announcement_user')->withPivot('read_at')->withTimestamps();
    }

    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class);
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::Youth->value,
        'status' => UserStatus::Active->value,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'email_verified_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }
}