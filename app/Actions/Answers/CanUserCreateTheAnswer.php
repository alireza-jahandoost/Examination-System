<?php

namespace App\Actions\Answers;

use App\Models\State;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Participant;

class CanUserCreateTheAnswer
{
    /**
     * check whether user can create the answer
     * @param  Question $question
     * @param  array    $inputs
     * @return array
     */
    public function check(Question $question, array $inputs): array
    {
        $state = $this->check_number_of_answers($question, $inputs);
        if ($state !== 'success') {
            return [
            'message' => $state,
            'status' => 422,
        ];
        }
        $state = $this->check_validation_of_answers($question, $inputs);
        if ($state !== 'success') {
            return [
            'message' => $state,
            'status' => 422,
        ];
        }

        return [
            'message' => $state,
            'status' => 200,
        ];
    }

    /**
     * check input is valid for question or not
     * @param  Question $question
     * @param  array    $inputs
     * @return string
     */
    protected function check_validation_of_answers(Question $question, array $inputs): string
    {
        $participant = Participant::where([
            'user_id' => auth()->id(),
            'exam_id' => $question->exam_id
        ])->first();

        switch ($question->questionType->name) {
            case 'descriptive':
                if (isset($inputs['integer_part'])) {
                    return 'descriptive questions do not need integer part';
                }
                return 'success';
            case 'fill the blank':
                if (isset($inputs['integer_part'])) {
                    return 'fill the blank questions do not need integer part';
                }
                return 'success';

            case 'multiple answer':
                if (isset($inputs['text_part'])) {
                    return 'multiple answer questions do not need text part';
                }
                if (Answer::where([
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                    'integer_answer' => $inputs['integer_part'],
                ])->exists()) {
                    return 'multiple answer can not have repeated answer';
                }

                $state = State::find($inputs['integer_part']);
                if (!$state || $state->question_id !== $question->id) {
                    return 'multiple answer is not valid';
                }
                return 'success';

            case 'select the answer':
                if (isset($inputs['text_part'])) {
                    return 'select answer questions do not need text part';
                }
                $state = State::find($inputs['integer_part']);
                if (!$state || $state->question_id !== $question->id) {
                    return 'select answer is not valid';
                }
                return 'success';

            case 'true or false':
                if (isset($inputs['text_part'])) {
                    return 'true or false questions do not need text part';
                }

                if ((int)$inputs['integer_part'] !== 0 && (int)$inputs['integer_part'] !== 1) {
                    return 'answer is not valid';
                }
                return 'success';

            case 'ordering':
                if (isset($inputs['text_part'])) {
                    return 'ordering questions do not need text part';
                }
                if (Answer::where([
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                    'integer_answer' => $inputs['integer_part'],
                ])->exists()) {
                    return 'ordering answer can not have repeated answer';
                }

                $state = State::find($inputs['integer_part']);
                if (!$state || $state->question_id !== $question->id) {
                    return 'the answer is not valid';
                }

                return 'success';

        }
    }

    /**
     * check that number of answers is valid
     * @param  Question $question
     * @param  array    $inputs
     * @return string
     */
    protected function check_number_of_answers(Question $question, array $inputs): string
    {
        $participant = Participant::where([
            'user_id' => auth()->id(),
            'exam_id' => $question->exam_id
        ])->first();
        $number_of_answers = Answer::where([
            'question_id' => $question->id,
            'participant_id' => $participant->id,
        ])->count();

        switch ($question->questionType->name) {
            case 'descriptive':
                if ($number_of_answers >= 1) {
                    return 'descriptive questions can just have 1 answer';
                }
                return 'success';

            case 'fill the blank':
                if ($number_of_answers >= 1) {
                    return 'fill the blank questions can just have 1 answer';
                }
                return 'success';

            case 'multiple answer':
                if ($number_of_answers >= $question->states()->count()) {
                    return 'multiple question has more answers as expected';
                }
                return 'success';

            case 'select the answer':
                if ($number_of_answers >= 1) {
                    return 'select questions can just have 1 answer';
                }
                return 'success';

            case 'true or false':
                if ($number_of_answers >= 1) {
                    return 'true or false questions can just have 1 answer';
                }
                return 'success';

            case 'ordering':
                if ($number_of_answers >= $question->states()->count()) {
                    return 'ordering question has more answers as expected';
                }
                return 'success';

        }
    }
}
