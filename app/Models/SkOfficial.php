<?php

namespace App\Models;

use Database\Factories\SkOfficialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkOfficial extends Model
{
    /** @use HasFactory<SkOfficialFactory> */
    use HasFactory;

    protected $fillable = [
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
}
