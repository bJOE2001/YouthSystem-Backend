<?php

namespace App\Models;

use Database\Factories\LydcMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LydcMember extends Model
{
    /** @use HasFactory<LydcMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'initials',
        'barangay',
        'contact',
        'email',
        'committee',
        'position',
        'organization',
        'sector',
        'responsibilities',
        'status',
    ];
}
