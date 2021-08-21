<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Exam;
use App\Models\Participant;

class ParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::chunk(20, function($users){
            foreach($users as $user){
                $cnt = Exam::count();
                $first = rand(1, $cnt);
                $second = rand(1, $cnt);
                while($first == $second) $second = rand(1, $cnt);

                Participant::factory()->for($user)->for(Exam::find($first))->create();
                Participant::factory()->for($user)->for(Exam::find($second))->create();
            }
        });
    }
}
