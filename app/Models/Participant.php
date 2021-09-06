<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $casts = [
        'exam_id' => 'integer',
        'user_id' => 'integer',
        'is_accepted' => 'boolean',
        'status' => 'integer',
    ];

    public function recalculateGrade()
    {
        $total_grade = 0;
        foreach($this->grades as $grade){
            $total_grade += $grade->grade;
        }

        $this->attributes['grade'] = $total_grade;
    }

    // RelationShips
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
    public function grades()
    {
        return $this->hasMany(QuestionGrade::class);
    }
}
