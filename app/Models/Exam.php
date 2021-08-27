<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $guarded = [
        'user_id',
        'password',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_score' => 'integer',
        'user_id' => 'integer',
        'confirmation_required' => 'boolean',
    ];

    //mutators
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    // RelationShips
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function participants()
    {
        return $this->hasMany(Participant::class);
    }
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
