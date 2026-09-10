<?php

namespace App\Models;

use Database\Factories\ScholarPositionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScholarPosition extends Model
{
    /** @use HasFactory<ScholarPositionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'can_scan',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_scan' => 'boolean',
        ];
    }

    public function scholars(): HasMany
    {
        return $this->hasMany(EcesproScholar::class, 'scholar_position_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
