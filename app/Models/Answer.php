<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    // RelationShips
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
