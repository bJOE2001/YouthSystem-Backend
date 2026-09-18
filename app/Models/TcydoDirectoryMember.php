<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TcydoDirectoryMember extends Model
{
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
