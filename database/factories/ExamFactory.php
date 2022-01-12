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
        $confirmation_required = (bool)rand(0, 1);
        $start = Carbon::now()->addDays(rand(1, 25))->addHours(rand(1, 24));
        $end = Carbon::make($start)->addHours(2);
        return [
            'name' => $this->faker->sentence(),
            'confirmation_required' => $confirmation_required,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'total_score' => 100,
            'published' => (config('app.env') === 'testing' ? false : true),
        ];
    }
}
