<?php

namespace App\Actions\Correcting;

use Illuminate\Support\Str;

use App\Models\Exam;

class CanAllTheExamCorrectBySystem
{

    /**
     * get a type id and return an array about that type
     *
     * @param  integer $type
     * @return array
     */
    public function can(Exam $exam)
    {
        foreach($exam->questions as $question){
            if(! $question->questionType->can_correct_by_system){
                return false;
            }
        }
        return true;
    }
}
