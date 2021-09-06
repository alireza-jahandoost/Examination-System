<?php

namespace App\Actions\Correcting;

use Illuminate\Support\Str;

use App\Models\Answer;
use App\Models\State;
use App\Models\Participant;

class CalculateGrade
{

    /**
     * get a type id and return an array about that type
     *
     * @param  integer $type
     * @return array
     */
    public function calculate(Participant $participant)
    {
        $action = new CalculateQuestionGrade;
        $total_grade = 0;

        foreach ($participant->exam->questions as $question) {
            $total_grade += $action->calculate($participant, $question);
        }

        return $total_grade;
    }

}
