<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\QuestionType;

use App\Actions\Questions\GetTypeOfQuestions;

class QuestionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(GetTypeOfQuestions $action)
    {
        $types = $action->getAll();

        foreach($types as $type){
            QuestionType::create($type);
        }
    }
}
