<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ExamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Exam::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $confirmation_required = (bool)rand(0,1);
        $password = $confirmation_required ? bcrypt('password') : null;
        $start = Carbon::now()->addDays(rand(1,25))->addHours(rand(1,24));
        $end = $start->add(2, 'hours');
        return [
            'name' => $this->faker->sentence(),
            'confirmation_required' => $confirmation_required,
            'password' => $password,
            'start' => $start->format('YYYY-MM-DD hh:mm:ss'),
            'end' => $end->format('YYYY-MM-DD hh:mm:ss'),
            'total_score' => 100,
        ];
    }
}
