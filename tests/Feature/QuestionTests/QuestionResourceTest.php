<?php

namespace Tests\Feature\QuestionTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Laravel\Sanctum\Sanctum;

use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionType;

use Database\Seeders\QuestionTypeSeeder;

class QuestionResourceTest extends TestCase
{
    use RefreshDatabase;

    public const QUESTION_SHOW_ROUTE = 'questions.show';
    /**
     * @test
     */
    public function question_resources_must_have_question_id()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::QUESTION_SHOW_ROUTE, [$exam->id, $question->id]));
        $response->assertStatus(200);
        $response->assertJsonStructure([
             'data' => [
                 'question' => [
                     'question_id',
                 ]
             ]
         ]);
    }
}
