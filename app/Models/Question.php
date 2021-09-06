<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Actions\Questions\GetTypeOfQuestions;

class Question extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
        'exam_id',
        'question_type_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'exam_id' => 'integer',
        'question_type_id' => 'integer',
        'score' => 'integer',
        'can_be_shuffled' => 'boolean',
    ];

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
    public function grades()
    {
        return $this->hasMany(QuestionGrade::class);
    }
}
