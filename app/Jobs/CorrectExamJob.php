<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use App\Models\Participant;
use App\Models\QuestionGrade;

use App\Actions\Correcting\CalculateQuestionGrade;
use App\Actions\Correcting\CanAllTheExamCorrectBySystem;

class CorrectExamJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $participant;
    public function __construct(Participant $participant)
    {
        $this->participant = $participant;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CalculateQuestionGrade $action1, CanAllTheExamCorrectBySystem $action2)
    {
        if ($this->participant->status > 1) {
            return;
        }

        $this->participant->grade = 0;

        foreach ($this->participant->exam->questions as $question) {
            if ($question->questionType->can_correct_by_system) {
                $questionGrade = new QuestionGrade();
                $questionGrade->participant_id = $this->participant->id;
                $questionGrade->question_id = $question->id;
                $questionGrade->grade = $action1->calculate($this->participant, $question);
                $questionGrade->save();

                $this->participant->grade += $questionGrade->grade;
            }
        }

        if ($action2->can($this->participant->exam)) {
            $this->participant->status = 3;
        } else {
            $this->participant->status = 2;
        }

        $this->participant->save();
    }
    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->participant->id))->dontRelease()];
    }
}
