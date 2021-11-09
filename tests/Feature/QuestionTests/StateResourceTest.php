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
use App\Models\State;
use App\Models\Participant;

use Carbon\Carbon;

use Database\Seeders\QuestionTypeSeeder;

class StateResourceTest extends TestCase
{
    use RefreshDatabase;

    public const STATE_SHOW_ROUTE = 'states.show';
    /**
    * @test
    */
    public function user_can_show_his_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        State::factory()->for($question)->count(5)->create([
            'integer_answer' => 1,
        ]);
        $state = State::factory()->for($question)->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_SHOW_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'state' => [
                    'state_id' => 6,
                    'text_part' => 'test',
                    'integer_part' => 1,
                    'question_id' => 1,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_see_question_link_when_showing_his_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        State::factory()->for($question)->count(5)->create([
            'integer_answer' => 1,
        ]);
        $state = State::factory()->for($question)->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_SHOW_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'state' => [
                    'quesiton_link' => route('questions.show', [$exam, $question]),
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_not_show_another_users_state()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $anotherUser = User::factory()->create();

        $exam = Exam::factory()->for($anotherUser)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        State::factory()->for($question)->count(5)->create([
            'integer_answer' => null,
        ]);
        $state = State::factory()->for($question)->create([
            'integer_answer' => null,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_SHOW_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function user_can_not_show_state_if_exam_and_question_do_not_match()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();
        $anotherExam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($anotherExam)->for($question_type)->create();

        State::factory()->for($question)->count(5)->create([
            'integer_answer' => null,
        ]);
        $state = State::factory()->for($question)->create([
            'integer_answer' => null,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_SHOW_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function user_can_not_show_a_state_if_state_and_question_do_not_match()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();
        $anotherQuestion = Question::factory()->for($exam)->for($question_type)->create();

        State::factory()->for($anotherQuestion)->count(5)->create([
            'integer_answer' => null,
        ]);
        $state = State::factory()->for($anotherQuestion)->create([
            'integer_answer' => null,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_SHOW_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function guest_user_can_not_show_any_state()
    {
        $this->seed(QuestionTypeSeeder::class);

        $user = User::factory()->create();

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        State::factory()->for($question)->count(5)->create([
            'integer_answer' => null,
        ]);
        $state = State::factory()->for($question)->create([
            'integer_answer' => null,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_SHOW_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(401);
    }
}
