<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'accepted_files',
        'required_status',
        'status',
    ];
}
