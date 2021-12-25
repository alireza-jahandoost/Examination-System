<?php

namespace Tests\Feature\QuestionTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Illuminate\Support\Str;

use Laravel\Sanctum\Sanctum;

use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\State;

use Database\Seeders\QuestionTypeSeeder;

class StateTest extends TestCase
{
    use RefreshDatabase;

    public const STATE_CREATE_ROUTE = 'states.store';
    public const STATE_UPDATE_ROUTE = 'states.update';
    public const STATE_SHOW_ROUTE = 'states.show';
    public const STATE_DELETE_ROUTE = 'states.destroy';

    public const STATE_COUNT_LIMIT = 8;

    /**
    * @test
    */
    public function user_can_create_a_state_for_its_question()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('states', 1);
    }

    /**
    * @test
    */
    public function user_can_not_create_more_than_limit_for_questions()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        for ($i = 0; $i < self::STATE_COUNT_LIMIT; $i++) {
            $response = $this->withHeaders([
                'Accept' => 'application/json'
                ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                    'text_part' => 'test',
                ]);

            $response->assertStatus(201);
        }
        for ($i = 0; $i < 8; $i++) {
            $response = $this->withHeaders([
                'Accept' => 'application/json'
                ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                    'text_part' => 'test',
                ]);

            $response->assertStatus(422);
        }
        $this->assertDatabaseCount('states', 8);
    }

    /**
    * @test
    */
    public function after_that_state_created_the_state_will_be_returned()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('states', 1);
        $response->assertJson([
            'data' => [
                'state' => [
                    'state_id' => 1,
                    'text_part' => 'test',
                    'integer_part' => null,
                    'question_id' => 1,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function for_creating_state_text_answer_must_be_valid()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => Str::repeat('a', 10001),
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('states', 0);
    }

    /**
    * @test
    */
    public function for_creating_state_integer_answer_must_be_valid()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(5);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 'aa',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('states', 0);
    }

    /**
    * @test
    */
    public function for_creating_a_state_text_answer_and_integer_answer_can_not_be_null_together()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('states', 0);
    }

    /**
    * @test
    */
    public function a_user_can_not_create_state_for_another_users_question()
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

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('states', 0);
    }

    /**
    * @test
    */
    public function a_user_just_can_create_new_state_for_a_question_if_the_specified_exam_is_its_exam_of_question()
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

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('states', 0);
    }

    /**
    * @test
    */
    public function a_guest_user_can_not_create_any_state()
    {
        $this->seed(QuestionTypeSeeder::class);

        $user = User::factory()->create();

        $exam = Exam::factory()->for($user)->create();
        $anotherExam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($anotherExam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test',
            ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('states', 0);
    }

    /**
    * @test
    */
    public function a_descriptive_question_can_not_have_any_state()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(1);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('states', 0);
        $response->assertJsonStructure([
            'message'
        ]);
    }

    /**
    * @test
    */
    public function a_fill_the_blank_question_can_have_multiple_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test',
            ]);

        $response->assertStatus(201);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test',
            ]);

        $second_response->assertStatus(201);
        $this->assertDatabaseCount('states', 2);
    }

    /**
    * @test
    */
    public function a_fill_the_blank_question_can_not_have_integer_part_state()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 123,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('states', 0);
        $response->assertJsonStructure([
            'message'
        ]);
    }

    /**
    * @test
    */
    public function a_multiple_answer_question_can_have_multiple_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
                'text_part' => 'test'
            ]);

        $response->assertStatus(201);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
                'text_part' => 'test',
            ]);

        $second_response->assertStatus(201);
        $this->assertDatabaseCount('states', 2);
    }

    /**
    * @test
    */
    public function in_multiple_answer_questions_integer_part_just_can_be_0_or_1()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
                'text_part' => 'test'
            ]);

        $response->assertStatus(201);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 0,
                'text_part' => 'test',
            ]);

        $second_response->assertStatus(201);
        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 2,
                'text_part' => 'test',
            ]);

        $third_response->assertStatus(422);
        $this->assertDatabaseCount('states', 2);
    }

    /**
    * @test
    */
    public function a_multiple_answer_question_must_have_integer_and_text_parts()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test'
            ]);

        $response->assertStatus(422);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
            ]);

        $second_response->assertStatus(422);
        $this->assertDatabaseCount('states', 0);
    }

    /**
    * @test
    */
    public function a_select_question_can_have_multiple_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
                'text_part' => 'test'
            ]);

        $response->assertStatus(201);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
                'text_part' => 'test',
            ]);

        $second_response->assertStatus(201);
        $this->assertDatabaseCount('states', 2);
    }

    /**
    * @test
    */
    public function in_select_questions_integer_part_just_can_be_0_or_1()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
                'text_part' => 'test'
            ]);

        $response->assertStatus(201);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 0,
                'text_part' => 'test',
            ]);

        $second_response->assertStatus(201);
        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 2,
                'text_part' => 'test',
            ]);

        $third_response->assertStatus(422);
        $this->assertDatabaseCount('states', 2);
    }

    /**
    * @test
    */
    public function a_select_question_must_have_integer_and_text_parts()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test'
            ]);

        $response->assertStatus(422);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
            ]);

        $second_response->assertStatus(422);
        $this->assertDatabaseCount('states', 0);
    }

    /**
    * @test
    */
    public function an_ordering_question_can_have_multiple_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
                'text_part' => 'test'
            ]);

        $response->assertStatus(201);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
                'text_part' => 'test',
            ]);

        $second_response->assertStatus(201);
        $this->assertDatabaseCount('states', 2);
    }

    /**
    * @test
    */
    public function in_ordering_questions_integer_part_just_can_be_between_1_and_the_number_of_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
                'text_part' => 'test'
            ]);

        $response->assertStatus(201);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 0,
                'text_part' => 'test',
            ]);

        $second_response->assertStatus(422);
        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 2,
                'text_part' => 'test',
            ]);

        $third_response->assertStatus(201);
        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 4,
                'text_part' => 'test',
            ]);

        $third_response->assertStatus(422);
        $this->assertDatabaseCount('states', 2);
    }

    /**
    * @test
    */
    public function an_ordering_question_must_have_integer_and_text_parts()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test'
            ]);

        $response->assertStatus(422);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
            ]);

        $second_response->assertStatus(422);
        $this->assertDatabaseCount('states', 0);
    }

    /**
    * @test
    */
    public function a_true_or_false_question_just_have_one_state()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(5);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1
            ]);

        $response->assertStatus(201);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
            ]);

        $second_response->assertStatus(422);
        $this->assertDatabaseCount('states', 1);
    }

    /**
    * @test
    */
    public function a_true_or_false_question_just_have_integer_part()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(5);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test'
            ]);

        $response->assertStatus(422);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
            ]);

        $second_response->assertStatus(201);
        $this->assertDatabaseCount('states', 1);
    }

    /**
    * @test
    */
    public function in_true_or_false_questions_integer_part_must_be_0_or_1()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(5);
        $question = Question::factory()->for($exam)->for($question_type)->create();
        $anotherQuestion = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 1,
            ]);

        $response->assertStatus(201);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $anotherQuestion]), [
                'integer_part' => 0,
            ]);

        $second_response->assertStatus(201);
        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'integer_part' => 2,
            ]);

        $third_response->assertStatus(422);
        $this->assertDatabaseCount('states', 2);
    }

    /**
    * @test
    */
    public function user_can_update_his_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'integer_answer' => 0,
            'text_answer' => 'test',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 1
            ]);

        $response->assertStatus(200);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test test');
        $this->assertTrue($state->integer_answer === 1);
    }

    /**
    * @test
    */
    public function user_will_receive_the_state_after_updating_it()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'integer_answer' => 0,
            'text_answer' => 'test',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 1
            ]);

        $response->assertStatus(200);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test test');
        $this->assertTrue($state->integer_answer === 1);
        $response->assertJson([
            'data' => [
                'state' => [
                    'state_id' => 1,
                    'text_part' => 'test test',
                    'integer_part' => 1,
                    'question_id' => 1
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_not_update_another_users_exams_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $anotherUser = User::factory()->create();

        $exam = Exam::factory()->for($anotherUser)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'integer_answer' => 0,
            'text_answer' => 'test',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 1
            ]);

        $response->assertStatus(403);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test');
        $this->assertTrue($state->integer_answer === 0);
    }

    /**
    * @test
    */
    public function user_can_not_update_an_state_with_wrong_exam_id_of_question()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();
        $anotherExam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($anotherExam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'integer_answer' => 0,
            'text_answer' => 'test',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 1
            ]);

        $response->assertStatus(403);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test');
        $this->assertTrue($state->integer_answer === 0);
    }

    /**
    * @test
    */
    public function user_can_not_update_an_state_with_wrong_question_id()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();
        $anotherQuestion = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($anotherQuestion)->create([
            'integer_answer' => 0,
            'text_answer' => 'test',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 1
            ]);

        $response->assertStatus(403);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test');
        $this->assertTrue($state->integer_answer === 0);
    }

    /**
    * @test
    */
    public function a_guest_user_can_not_update_any_state()
    {
        $this->seed(QuestionTypeSeeder::class);

        $user = User::factory()->create();

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'integer_answer' => 0,
            'text_answer' => 'test',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 1
            ]);

        $response->assertStatus(401);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test');
        $this->assertTrue($state->integer_answer === 0);
    }

    /**
    * @test
    */
    public function user_will_receive_a_message_if_his_state_updating_is_not_ok_for_question()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'text_answer' => 'test',
            'integer_answer' => null
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 1
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message'
        ]);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test');
        $this->assertTrue($state->integer_answer === null);
    }

    /**
    * @test
    */
    public function a_fill_the_blank_question_can_not_have_integer_part_after_update()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'text_answer' => 'test',
            'integer_answer' => null
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 1
            ]);

        $response->assertStatus(422);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => '',
            ]);

        $second_response->assertStatus(422);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test');
        $this->assertTrue($state->integer_answer === null);
    }

    /**
    * @test
    */
    public function a_multiple_answer_question_must_be_valid_after_update()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'text_answer' => 'test',
            'integer_answer' => 1
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 2
            ]);

        $response->assertStatus(422);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => '',
                'integer_part' => 1
            ]);

        $second_response->assertStatus(422);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test');
        $this->assertTrue($state->integer_answer === 1);
    }

    /**
    * @test
    */
    public function a_select_question_must_be_valid_after_update()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'text_answer' => 'test',
            'integer_answer' => 1
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 2
            ]);

        $response->assertStatus(422);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => '',
                'integer_part' => 1
            ]);

        $second_response->assertStatus(422);
        $state->refresh();
        $this->assertTrue($state->text_answer === 'test');
        $this->assertTrue($state->integer_answer === 1);
    }

    /**
    * @test
    */
    public function a_true_or_false_question_must_be_valid_after_update()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(5);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'integer_answer' => 0
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 1
            ]);

        $response->assertStatus(422);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'integer_part' => 2
            ]);

        $second_response->assertStatus(422);
        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'integer_part' => 1
            ]);
        $third_response->assertStatus(200);
        $state->refresh();
        $this->assertTrue($state->integer_answer === 1);
    }

    /**
    * @test
    */
    public function an_ordering_question_must_be_valid_after_update()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->create([
            'integer_answer' => 1
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test',
                'integer_part' => 2
            ]);

        $response->assertStatus(422);
        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'integer_part' => 0
            ]);

        $second_response->assertStatus(422);
        $state->refresh();
        $this->assertTrue($state->integer_answer === 1);
    }

    /**
    * @test
    */
    public function user_can_delete_his_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

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
            ])->delete(route(self::STATE_DELETE_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(202);
        $this->assertDeleted($state);
    }

    /**
    * @test
    */
    public function after_deleting_an_ordering_question_states_with_bigger_integer_answer_must_decrease()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        for ($i=1;$i<=5;$i++) {
            State::factory()->for($question)->create([
                'integer_answer' => $i,
            ]);
        }

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(self::STATE_DELETE_ROUTE, [$exam, $question, State::where('integer_answer', 3)->first()]));

        $response->assertStatus(202);
        $this->assertDatabaseCount('states', 4);

        for ($i=1;$i<=4;$i++) {
            $this->assertTrue(State::where('integer_answer', $i)->exists());
        }
    }

    /**
    * @test
    */
    public function user_must_own_the_exam_to_delete_its_state()
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
            ])->delete(route(self::STATE_DELETE_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('states', ['id' => $state->id]);
    }

    /**
    * @test
    */
    public function for_deleting_state_question_must_belong_to_exam()
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
            ])->delete(route(self::STATE_DELETE_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('states', ['id' => $state->id]);
    }

    /**
    * @test
    */
    public function for_deleting_state_that_state_must_belong_to_specific_question()
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
            ])->delete(route(self::STATE_DELETE_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('states', ['id' => $state->id]);
    }

    /**
    * @test
    */
    public function a_guest_user_can_not_delete_any_state()
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
            ])->delete(route(self::STATE_DELETE_ROUTE, [$exam, $question, $state]));

        $response->assertStatus(401);
        $this->assertDatabaseHas('states', ['id' => $state->id]);
    }
}
