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
}
