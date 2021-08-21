<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\Question;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Exam::chunk(20, function($exams){
            foreach($exams as $exam){
                Question::factory()->for($exam)->count(5)->create();
            }
        });
    }
}
