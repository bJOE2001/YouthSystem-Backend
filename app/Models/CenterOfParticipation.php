<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CenterOfParticipation extends Model

{
    use HasFactory;
    protected $table = 'center_of_participation';

    protected $fillable = [
        'name',
        'status'
    ];
}
