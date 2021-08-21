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
        Question::chunk(20, function($questions){
            foreach($questions as $question){
                State::factory()->for($question)->count(2)->create();
            }
        });
    }
}
