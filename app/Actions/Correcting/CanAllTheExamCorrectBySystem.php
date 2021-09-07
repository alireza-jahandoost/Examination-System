<?php

namespace App\Actions\Correcting;

use Illuminate\Support\Str;

use App\Models\Exam;

class CanAllTheExamCorrectBySystem
{
    /**
     * checks whether exams can be currected by system
     * @param  Exam $exam
     * @return bool
     */
    public function can(Exam $exam): bool
    {
        foreach ($exam->questions as $question) {
            if (! $question->questionType->can_correct_by_system) {
                return false;
            }
        }
        return true;
    }
}
