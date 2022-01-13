<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionType extends Model
{
    use HasFactory;

    protected $casts = [
        'number_of_states' => 'integer',
    ];

    // RelationShips
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
