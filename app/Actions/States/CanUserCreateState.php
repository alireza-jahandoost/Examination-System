<?php

namespace App\Actions\States;

use Illuminate\Support\Str;

use App\Models\Question;
use App\Models\State;

class CanUserCreateState
{

    const STATE_COUNT_LIMIT = 8;

    /**
     * get a type id and return an array about that type
     *
     * @param  integer $type
     * @return array
     */
    public function check(Question $question, $inputs)
    {
        $count_of_states = $question->states()->count();
        $question_type = $question->questionType->type_of_answer;

        $textAvailable = isset($inputs['text_part']);
        $integerAvailable = isset($inputs['integer_part']);

        $success_states =
            ($this->integer_part_is_valid($question, $inputs) && (
            ($question_type === 'text' && $textAvailable && ! $integerAvailable) ||
            ($question_type === 'integer' && $integerAvailable && !$textAvailable) ||
            ($question_type === 'text and integer' && $integerAvailable && $textAvailable)
        ));

        $user_can_create_more_states = (
            $question->questionType->number_of_states === 'multiple' ||
            $count_of_states + 1 <= $question->questionType->number_of_states
        );

        if($count_of_states >= self::STATE_COUNT_LIMIT){
            return 'this question can not have more than '.self::STATE_COUNT_LIMIT.' states';
        }

        if($user_can_create_more_states){
             if($success_states){
                 return 'success';
             }else{
                 return 'state inputs do not match with type of question';
             }
        }else{
            return 'User can not create more state for this question';
        }
    }

    protected function integer_part_is_valid(Question $question, $inputs)
    {
        $value = $inputs['integer_part'] ?? null;
        $number_of_states = $question->states()->count();
        switch ($question->questionType->id) {
            case 3:
                return ($value === 0 || $value === 1);

            case 4:
                return ($value === 0 || $value === 1);

            case 5:
                return ($value === 0 || $value === 1);

            case 6:
                return ($value >= 1 && $value <= $number_of_states + 1);

            default:
                return true;
        }
    }
}