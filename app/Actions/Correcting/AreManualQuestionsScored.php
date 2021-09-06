<?php

namespace App\Actions\Correcting;

use Illuminate\Support\Str;

use App\Models\Exam;
use App\Models\Participant;

class AreManualQuestionsScored
{

    /**
     * get a type id and return an array about that type
     *
     * @param  integer $type
     * @return array
     */
    public function check(Exam $exam, Participant $participant)
    {
        foreach($exam->questions as $question){
            if(! $question->questionType->can_correct_by_system){
                switch ($question->questionType->id) {
                    case 1:
                        $answer = $question->answers()->where('participant_id', $participant->id)->first();
                        if(!$answer || !$answer->scored){
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
