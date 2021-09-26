<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionType;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Exam::chunk(20, function ($exams) {
            $questionType = QuestionType::find(rand(1, 6));
            foreach ($exams as $exam) {
                Question::factory()->for($exam)->for($questionType)->count(5)->create();
            }
        });
    }
}
