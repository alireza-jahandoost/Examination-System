<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        if (config('app.env' === 'production')) {
            $this->call([
                QuestionTypeSeeder::class,
            ]);
        } else {
            $this->call([
            UserSeeder::class,
            ExamSeeder::class,
            QuestionTypeSeeder::class,
            ParticipantSeeder::class,
            QuestionSeeder::class,
            StateSeeder::class,
            AnswerSeeder::class,
        ]);
        }
    }
}
