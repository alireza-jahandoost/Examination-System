<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionGrade extends Model
{
    use HasFactory;

    // Relationships
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
