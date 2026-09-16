<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcesproExamQuestionnaire extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description'];

    public function questions()
    {
        return $this->hasMany(EcesproExamQuestion::class, 'questionnaire_id');
    }
}