<?php

namespace App\Actions\Correcting;

use App\Models\Exam;
use App\Models\QuestionGrade;
use App\Models\Participant;

class AreManualQuestionsScored
{
    /**
     * check whether manual questions scored
     * @param  Exam        $exam
     * @param  Participant $participant
     * @return bool
     */
    public function check(Exam $exam, Participant $participant): bool
    {
        foreach ($exam->questions as $question) {
            if (! $question->questionType->can_correct_by_system) {
                switch ($question->questionType->id) {
                    case 1:
                        $questionGrade = QuestionGrade::where([
                            'question_id' => $question->id,
                            'participant_id' => $participant->id
                        ])->first();

                        if (!$questionGrade) {
                            return false;
                        }
                        break;

                    default:
                        dd('unknown type of question(AreManualQuestionsScored)');
                        break;
                }
            }
        }
        return true;
    }
}
