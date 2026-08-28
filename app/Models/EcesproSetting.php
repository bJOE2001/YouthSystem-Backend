<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EcesproSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Get a setting value by key with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("ecespro_setting_{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (! $setting || $setting->value === null) {
                if ($key === 'required_volunteer_hours' && $default === null) {
                    return 36.00;
                }

                return $default;
            }

            return $setting->value;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): self
    {
        Cache::forget("ecespro_setting_{$key}");

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
