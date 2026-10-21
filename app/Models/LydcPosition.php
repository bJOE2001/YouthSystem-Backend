<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LydcPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
    ];
}
