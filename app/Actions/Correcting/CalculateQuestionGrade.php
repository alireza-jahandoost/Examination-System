<?php

namespace App\Actions\Correcting;

use App\Models\Answer;
use App\Models\Participant;
use App\Models\Question;
use App\Models\State;

class CalculateQuestionGrade
{
    /**
     * calculate the value of question
     * @param  Participant $participant
     * @param  Question    $question
     * @return int
     */
    public function calculate(Participant $participant, Question $question): int
    {
        switch ($question->questionType->id) {
            case 1:
                return 0;

            case 2:
                $answer = Answer::where([
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                ])->first();

                if (!$answer) {
                    return 0;
                }

                $is_answer_correct = State::where([
                    'question_id' => $question->id,
                    'text_answer' => $answer->text_answer,
                ])->exists();

                if ($is_answer_correct) {
                    return $question->score;
                } else {
                    return 0;
                }

                // no break
            case 3:
                foreach ($question->states as $state) {
                    $answer = Answer::where([
                        'question_id' => $question->id,
                        'integer_answer' => $state->id,
                        'participant_id' => $participant->id,
                    ])->exists();

                    if ($answer) {
                        if (! $state->integer_answer) {
                            return 0;
                        }
                    } else {
                        if ($state->integer_answer) {
                            return 0;
                        }
                    }
                }
                return $question->score;

            case 4:
                $answer = Answer::where([
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                ])->first();
                $correct_answer = $question->states()->where('integer_answer', 1)->first();

                if (!$answer || $answer->integer_answer !== $correct_answer->id) {
                    return 0;
                } else {
                    return $question->score;
                }

                // no break
            case 5:
                $answer = Answer::where([
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                ])->first();
                if ($answer) {
                    $answer = $answer->integer_answer;
                } else {
                    return 0;
                }

                $correct_answer = $question->states()->first()->integer_answer;

                if ($answer === $correct_answer) {
                    return $question->score;
                } else {
                    return 0;
                }

                // no break
            case 6:
                $answers = Answer::where([
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                ])->orderBy('id')->get();

                $correct_answers = $question->states()->orderBy('integer_answer')->get();

                if (count($answers) < count($correct_answers)) {
                    return 0;
                }

                for ($i = 0;$i < count($correct_answers); $i++) {
                    if ($correct_answers[$i]->id !== $answers[$i]->integer_answer) {
                        return 0;
                    }
                }
                return $question->score;
            default:
                // code...
                break;
        }
    }
}
