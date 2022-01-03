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
        Participant::chunk(20, function ($participants) {
            foreach ($participants as $participant) {
                $exam = $participant->exam;
                foreach ($exam->questions as $question) {
                    switch ($question->question_type_id) {
                        case 1:
                        case 2:
                            Answer::factory()->for($question)->for($participant)->create([
                                'integer_answer' => null,
                            ]);
                            break;
                        case 3:
                        case 4:
                            Answer::factory()->for($question)->for($participant)->create([
                                'text_answer' => null,
                                'integer_answer' => $question->states[0]->id,
                            ]);
                            break;
                        case 5:
                            Answer::factory()->for($question)->for($participant)->create([
                                'text_answer' => null,
                                'integer_answer' => rand(0, 1),
                            ]);
                            break;
                        case 6:
                            foreach ($question->states as $state) {
                                Answer::factory()->for($question)->for($participant)->create([
                                    'text_answer' => null,
                                    'integer_answer' => $state->id,
                                ]);
                            }
                            break;

                        default:
                            throw new Error('unexpected question type in answer seeder');
                            break;
                    }
                }
            }
        });
    }
}
