<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class Participant extends Model
{
    use HasFactory;

    protected $casts = [
        'exam_id' => 'integer',
        'user_id' => 'integer',
        'is_accepted' => 'boolean',
        'status' => 'integer',
    ];

    // Accessors
    public function getTextStatusAttribute()
    {
        $change_status = [
            0 => 'NOT_FINISHED',
            1 => 'IN_PROCESSING',
            2 => 'WAIT_FOR_MANUAL_CORRECTING',
            3 => 'FINISHED',
        ];
        return $change_status[$this->attributes['status']];
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value;
        $this->attributes['status_changed_at'] = Carbon::now()->toDateTimeString();
    }

    /**
     * calculate the grade of participant based on calculated answers
     */
    public function recalculateGrade()
    {
        $total_grade = 0;
        foreach ($this->grades as $grade) {
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
