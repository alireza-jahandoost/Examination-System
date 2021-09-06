<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Participant;
use App\Models\User;
use App\Models\Exam;

use App\Actions\Correcting\CalculateGrade;
use App\Actions\Correcting\CanAllTheExamCorrectBySystem;

class CorrectExamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(CalculateGrade $action1, CanAllTheExamCorrectBySystem $action2)
    {
        $this->participant->grade = $action1->calculate($this->participant);

        if($action2->can($this->participant->exam)){
            $this->participant->status = 3;
        }else{
            $this->participant->status = 2;
        }

        $this->participant->save();
    }
}
