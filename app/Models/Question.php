<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    public function type()
    {
        switch ($this->attributes['type']) {
            case 1:
                return "Descriptive";
                break;
            case 2:
                return "FillTheBlank";
                break;
            case 3:
                return "Multiple";
                break;
            case 4:
                return "SelectTheAnswer";
                break;
            case 5:
                return "TrueOrFalse";
                break;
            case 6:
                return "Ordering";
                break;
            default:
                return "Invalid";
                break;
        }
    }

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
}
