<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Models\Question;

use Carbon\Carbon;

class CanExamBePublished
{
    /**
     * check that exam can be published or not
     * @param  Exam   $exam
     * @return string
     */
    public function check(Exam $exam): string
    {
        $sum_of_scores = $exam->questions->reduce(function ($carry, $question) {
            return $carry + $question->score;
        }, 0);

        $start_of_exam = Carbon::make($exam->start);
        $end_of_exam = Carbon::make($exam->end);
        if ($start_of_exam <= Carbon::now()) {
            return 'start time has passed';
        } elseif ($start_of_exam >= $end_of_exam) {
            return 'ending time of exam must be after the start';
        }


        if ($sum_of_scores !== $exam->total_score) {
            return 'sum of scores of questions is not equal to total score of exam';
        }


        foreach ($exam->questions as $question) {
            $status = $this->has_minimums($question);
            if ($status !== 'success') {
                return $status;
            }
        }


        foreach ($exam->questions as $question) {
            $status = $this->has_valid_answer($question);
            if ($status !== 'success') {
                return $status;
            }
        }
        return 'success';
    }

    /**
     * check that question has the lower bound of states
     * @param  Question $question
     * @return string
     */
    protected function has_minimums(Question $question): string
    {
        switch ($question->questionType->id) {
            case 1:
                if ($question->states()->count() === 0) {
                    return 'success';
                }
                return 'descriptive questions do not have to got state';

            case 2:
                if ($question->states()->count() >= 1) {
                    if (substr_count($question->question_text, '{{{}}}') === 1) {
                        return 'success';
                    }
                    return 'the place of blank input must be specified by "{{{}}}" in question text - just one place is allowed';
                } else {
                    return 'fill the blank questions must have 1 state';
                }

                // no break
            case 3:
                if ($question->states()->count() >= 2) {
                    return 'success';
                }
                return 'multiple questions must have more than 2 states';

            case 4:
                if ($question->states()->count() >= 2) {
                    return 'success';
                }
                return 'select questions must have more than 2 states';

            case 5:
                if ($question->states()->count() === 1) {
                    return 'success';
                }
                return 'true or false questions must have 1 state';

            case 6:
                if ($question->states()->count() >= 2) {
                    return 'success';
                }
                return 'ordering questions must have more than 2 states';

            default:
                return 'invalid type of question';
        }
    }

    /**
     * check that the answer of question exists and valid
     * @param  Question $question
     * @return string
     */
    protected function has_valid_answer(Question $question): string
    {
        switch ($question->questionType->id) {
            case 1:
            case 2:
            case 5:
                return 'success';
            case 3:
                return $question->states()->where('integer_answer', 1)->exists() ? 'success' : 'multiple questions must have at least one answer';
            case 4:
                return $question->states()->where('integer_answer', 1)->count() === 1 ? 'success' : 'select questions must have at least one answer';
            case 6:
                $answers = $question->states()->orderBy('integer_answer')->pluck('integer_answer');
                $iterator = 1;
                foreach ($answers as $answer) {
                    if ($iterator === $answer) {
                        $iterator++;
                    }
                }
                return $iterator === count($answers) + 1 ? 'success' : 'orders must start from 1 and must dont have repeated order';

            default:
                // code...
                break;
        }
    }
}
