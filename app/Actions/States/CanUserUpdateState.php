<?php

namespace App\Actions\States;

use App\Models\Question;

class CanUserUpdateState
{
    /**
     * check user can update states of question or not
     * @param  Question $question
     * @param  array    $inputs
     * @return string
     */
    public function check(Question $question, array $inputs): string
    {
        $question_type = $question->questionType->type_of_answer;

        $textAvailable = isset($inputs['text_part']);
        $integerAvailable = isset($inputs['integer_part']);

        $success_states =
            ($this->integer_part_is_valid($question, $inputs) && (
                ($question_type === 'text' && $textAvailable && ! $integerAvailable) ||
            ($question_type === 'integer' && $integerAvailable && !$textAvailable) ||
            ($question_type === 'text and integer' && $integerAvailable && $textAvailable)
            ));

        if ($success_states) {
            return 'success';
        } else {
            return 'state inputs do not match with type of question';
        }
    }

    /**
     * check integer part of input is valid of not
     * @param  Question $question
     * @param  array    $inputs
     * @return bool
     */
    protected function integer_part_is_valid(Question $question, array $inputs): bool
    {
        $value = $inputs['integer_part'] ?? null;
        if ($value !== null) {
            $value = (int)$value;
        }
        $number_of_states = $question->states()->count();
        switch ($question->questionType->id) {
            case 3:
                return ($value === 0 || $value === 1);

            case 4:
                return ($value === 0 || $value === 1);

            case 5:
                return ($value === 0 || $value === 1);

            case 6:
                return ($value >= 1 && $value <= $number_of_states);

            default:
                return true;
        }
    }
}
