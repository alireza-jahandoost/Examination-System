<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Actions\Questions\GetTypeOfQuestions;

class Question extends Model
{
    use HasFactory;

    // RelationShips
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    public function states()
    {
        return $this->hasMany(State::class);
    }
    public function questionType()
    {
        return $this->belongsTo(QuestionType::class);
    }
}
