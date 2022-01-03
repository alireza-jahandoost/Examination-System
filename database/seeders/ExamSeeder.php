<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Exam;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cnt = User::count();
        for ($i = 0;$i < 300;$i ++) {
            $user = User::find(rand(1, $cnt));
            Exam::factory()->for($user)->create();
        }
    }
}
