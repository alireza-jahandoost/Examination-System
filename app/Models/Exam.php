<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Exam extends Model
{
    use HasFactory;
    use Searchable;

    protected $guarded = [
        'id',
        'user_id',
        'password',
        'created_at',
        'updated_at',
        'published',
    ];

    protected $casts = [
        'total_score' => 'integer',
        'user_id' => 'integer',
        'confirmation_required' => 'boolean',
        'published' => 'boolean',
    ];

    public function shouldBeSearchable()
    {
        return $this->published;
    }

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
