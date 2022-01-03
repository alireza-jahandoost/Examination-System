<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\State;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Question::chunk(20, function ($questions) {
            foreach ($questions as $question) {
                switch ($question->question_type_id) {
                    case 1:
                        break;
                    case 2:
                        State::factory()->for($question)->count(3)->create([
                            'integer_answer' => null
                        ]);
                        break;
                    case 3:
                        State::factory()->for($question)->count(5)->create([
                            'integer_answer' => rand(0, 1),
                        ]);
                        break;
                    case 4:
                        State::factory()->for($question)->count(4)->create([
                            'integer_answer' => 0,
                        ]);
                        State::factory()->for($question)->create([
                            'integer_answer' => 1,
                        ]);
                        break;
                    case 5:
                        State::factory()->for($question)->create([
                            'integer_answer' => rand(0, 1),
                        ]);
                        break;
                    case 6:
                        for ($i=0;$i<5;$i++) {
                            State::factory()->for($question)->create([
                                'integer_answer' => $i+1,
                            ]);
                        }
                        break;

                    default:
                        throw new Error('unexpected question type id in state seeder');
                        break;
                }
            }
        });
    }
}
