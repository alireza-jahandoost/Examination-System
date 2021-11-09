<?php

namespace App\Actions\States;

use App\Models\Question;

class WhichStateColumnsMustBeSend
{
    /**
     * return the columns that must be sent to user
     * @param  Question $question
     * @param  array    $inputs
     * @return array
     */
    public function check(Question $question): array
    {
        if ($question->exam->user_id === auth()->id()) {
            return ['id', 'integer_answer', 'text_answer'];
        }

        switch ($question->questionType->id) {
            case 3:
            case 4:
            case 6:
                return ['id', 'text_answer'];
                break;

            default:
                return [];
                break;
        }
    }
}
