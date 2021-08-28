<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionType extends Model
{
    use HasFactory;

    // RelationShips
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

}
