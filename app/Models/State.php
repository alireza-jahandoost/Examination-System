<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
        'question_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'integer_answer' => 'integer',
        'question_id' => 'integer',
    ];

    // RelationShips
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
