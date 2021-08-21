<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Participant;
use App\Models\Answer;

class AnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Participant::chunk(20, function($participants){
            foreach($participants as $participant){
                $exam = $participant->exam;
                foreach($exam->questions as $question){
                    Answer::factory()->for($question)->for($participant)->create();
                }
            }
        });
    }
}
