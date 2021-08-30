<?php

namespace App\Actions\Exams;

use Illuminate\Support\Str;

use App\Models\Exam;
use App\Models\Question;

class CanExamBePublished
{

    /**
     * get a type id and return an array about that type
     *
     * @param  integer $type
     * @return array
     */
    public function check(Exam $exam)
    {
        $sum_of_scores = $exam->questions->reduce(function($carry, $question){
            return $carry + $question->score;
        }, 0);

        if($sum_of_scores !== $exam->total_score){
            return 'sum of scores of questions is not equal to total score of exam';
        }


        foreach ($exam->questions as $question) {
            $status = $this->has_minimums($question);
            if($status !== 'success'){
                return $status;
            }
        }


        foreach ($exam->questions as $question) {
            $status = $this->has_enough_answer($question);
            if($status !== 'success'){
                return $status;
            }
        }
        return 'success';
    }

    protected function has_minimums(Question $question)
    {
        switch($question->questionType->id){
            case 1:
                if($question->states()->count() === 0) return 'success';
                return 'descriptive questions do not have to got state';

            case 2:
                if($question->states()->count() >= 1) return 'success';
                return 'fill the blank questions must have 1 state';

            case 3:
                if($question->states()->count() >= 2) return 'success';
                return 'multiple questions must have more than 2 states';

            case 4:
                if($question->states()->count() >= 2) return 'success';
                return 'select questions must have more than 2 states';

            case 5:
                if($question->states()->count() === 1) return 'success';
                return 'true or false questions must have 1 state';

            case 6:
                if($question->states()->count() >= 2) return 'success';
                return 'ordering questions must have more than 2 states';

            default:
                return 'invalid type of question';
        }
    }

    protected function has_enough_answer(Question $question)
    {
        switch ($question->questionType->id) {
            case 1:
            case 2:
            case 5:
                return 'success';
            case 3:
                return $question->states()->where('integer_answer', 1)->exists() ? 'success' : 'multiple questions must have atleast one answer';
            case 4:
                return $question->states()->where('integer_answer', 1)->exists() ? 'success' : 'select questions must have atleast one answer';
            case 6:
                $answers = $question->states()->orderBy('integer_answer')->pluck('integer_answer');
                $iterator = 1;
                foreach ($answers as $answer) {
                    if($iterator === $answer) $iterator++;
                }
                return $iterator === count($answers) + 1 ? 'success' : 'orders must start from 1 and must dont have repeated order';

            default:
                // code...
                break;
        }
    }
}
