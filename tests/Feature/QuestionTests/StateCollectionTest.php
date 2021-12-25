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

class StateCollectionTest extends TestCase
{
    use RefreshDatabase;
    public const STATE_INDEX_ROUTE = 'states.index';

    /**
    * @test
    */
    public function user_can_receive_his_states_of_his_questions()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create([
            'integer_answer' => null,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'states' => [
                    [
                        'state_id',
                        'text_part',
                        'integer_part',
                        'question_id',
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function for_fill_the_blank_questions_owner_of_exam_will_receive_states_in_original_order()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $owner = User::factory()->create()
        );
        $user = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
            'published' => true,
            'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
            'end' => $end->format('Y-m-d H:i:s')
          ]
        );

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $state_ids = array_map(fn ($state) => $state['state_id'], $response->json()['data']['states']);

        $check = true;
        for ($i = 0;$i < 5;$i ++) {
            if ($state_ids[$i] !== $i+1) {
                $check = false;
            }
        }
        $this->assertTrue($check);
    }

    /**
    * @test
    */
    public function for_multiple_answer_questions_owner_of_exam_will_receive_states_in_original_order()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $owner = User::factory()->create()
        );
        $user = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
            'published' => true,
            'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
            'end' => $end->format('Y-m-d H:i:s')
          ]
        );

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $state_ids = array_map(fn ($state) => $state['state_id'], $response->json()['data']['states']);

        $check = true;
        for ($i = 0;$i < 5;$i ++) {
            if ($state_ids[$i] !== $i+1) {
                $check = false;
            }
        }
        $this->assertTrue($check);
    }

    /**
    * @test
    */
    public function for_select_the_answer_questions_owner_of_exam_will_receive_states_in_original_order()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $owner = User::factory()->create()
        );
        $user = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
            'published' => true,
            'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
            'end' => $end->format('Y-m-d H:i:s')
          ]
        );

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $state_ids = array_map(fn ($state) => $state['state_id'], $response->json()['data']['states']);

        $check = true;
        for ($i = 0;$i < 5;$i ++) {
            if ($state_ids[$i] !== $i+1) {
                $check = false;
            }
        }
        $this->assertTrue($check);
    }

    /**
    * @test
    */
    public function for_ordering_questions_owner_of_exam_will_receive_states_in_original_order()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $owner = User::factory()->create()
        );
        $user = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
            'published' => true,
            'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
            'end' => $end->format('Y-m-d H:i:s')
          ]
        );

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $state_ids = array_map(fn ($state) => $state['state_id'], $response->json()['data']['states']);

        $check = true;
        for ($i = 0;$i < 5;$i ++) {
            if ($state_ids[$i] !== $i+1) {
                $check = false;
            }
        }
        $this->assertTrue($check);
    }

    /**
    * @test
    */
    public function user_can_not_see_another_users_states()
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

        $state = State::factory()->for($question)->count(5)->create([
            'integer_answer' => null,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function user_indexing_states_question_must_belong_to_exam()
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

        $state = State::factory()->for($question)->count(5)->create([
            'integer_answer' => null,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function guest_user_can_not_index_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        $user = User::factory()->create();

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create([
            'integer_answer' => null,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function for_descriptive_questions_registered_user_in_exam_can_not_see_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
                'published' => true,
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
            ]
        );

        $question_type = QuestionType::find(1);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        // $state = State::factory()->for($question)->count(5)->create([
        //     'integer_answer' => null,
        // ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function for_fill_the_blank_questions_registered_user_in_exam_can_not_see_the_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
                'published' => true,
                'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
                'end' => $end->format('Y-m-d H:i:s')
            ]
        );

        $question_type = QuestionType::find(2);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create([
            'integer_answer' => null,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function for_multiple_answer_questions_registered_user_in_exam_can_see_state_id_and_text_part()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
                'published' => true,
                'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
                'end' => $end->format('Y-m-d H:i:s')
            ]
        );

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'states' => [
                    [
                        'text_part' => $state[0]->text_answer,
                        'state_id' => $state[0]->id,
                        'question_id' => $question->id,
                    ]
                ]
            ]
        ]);

        $response->assertJsonMissing([
            'data' => [
                'states' => [
                    [
                        'integer_part'
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function for_multiple_answer_questions_registered_user_in_exam_will_receive_states_in_original_order()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
            'published' => true,
            'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
            'end' => $end->format('Y-m-d H:i:s')
          ]
        );

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $state_ids = array_map(fn ($state) => $state['state_id'], $response->json()['data']['states']);

        $check = true;
        for ($i = 0;$i < 5;$i ++) {
            if ($state_ids[$i] !== $i+1) {
                $check = false;
            }
        }
        $this->assertTrue($check);
    }

    /**
    * @test
    */
    public function for_select_the_answer_questions_registered_user_in_exam_can_see_state_id_and_text_part()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
                'published' => true,
                'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
                'end' => $end->format('Y-m-d H:i:s')
            ]
        );

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'states' => [
                    [
                        'text_part' => $state[0]->text_answer,
                        'state_id' => $state[0]->id,
                        'question_id' => $question->id,
                    ]
                ]
            ]
        ]);

        $response->assertJsonMissing([
            'data' => [
                'states' => [
                    [
                        'integer_part'
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function for_select_the_answer_questions_registered_user_in_exam_will_receive_states_in_original_order()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
            'published' => true,
            'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
            'end' => $end->format('Y-m-d H:i:s')
          ]
        );

        $question_type = QuestionType::find(4);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $state_ids = array_map(fn ($state) => $state['state_id'], $response->json()['data']['states']);

        $check = true;
        for ($i = 0;$i < 5;$i ++) {
            if ($state_ids[$i] !== $i+1) {
                $check = false;
            }
        }
        $this->assertTrue($check);
    }

    /**
    * @test
    */
    public function for_true_or_false_questions_registered_user_in_exam_can_not_see_the_state()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
                'published' => true,
                'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
                'end' => $end->format('Y-m-d H:i:s')
            ]
        );

        $question_type = QuestionType::find(5);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(1)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function for_ordering_questions_registered_user_in_exam_can_see_state_id_and_text_part()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
            'published' => true,
            'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
            'end' => $end->format('Y-m-d H:i:s')
          ]
        );

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $this->assertTrue(array_reduce($response->json()['data']['states'], function ($carry, $currentState) use ($state, $question) {
            ['text_part' => $textPart, 'state_id' => $stateId, 'question_id' => $questionId] = $currentState;
            return ($carry or ($textPart === $state[0]->text_answer &&
                               $stateId === $state[0]->id &&
                               $questionId === $question->id));
        }, false));

        $response->assertJsonMissing([
            'data' => [
                'states' => [
                    [
                        'integer_part'
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function for_ordering_questions_registered_user_in_exam_will_receive_states_in_changed_order()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
            'published' => true,
            'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
            'end' => $end->format('Y-m-d H:i:s')
          ]
        );

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);

        $state_ids = array_map(fn ($state) => $state['state_id'], $response->json()['data']['states']);

        $check = true;
        for ($i = 0;$i < 5;$i ++) {
            if ($state_ids[$i] !== $i+1) {
                $check = false;
            }
        }
        $this->assertFalse($check);
    }

    /**
    * @test
    */
    public function if_exam_is_not_started_registered_user_in_exam_can_not_see_the_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->addMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
                'published' => true,
                'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
                'end' => $end->format('Y-m-d H:i:s')
            ]
        );

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function if_exam_is_ended_registered_users_can_see_the_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subHours(3);
        $end = Carbon::now()->subHours(1);
        $exam = Exam::factory()->for($owner)->create(
            [
                'published' => true,
                'start' => $start->format('Y-m-d H:i:s'),
                'confirmation_required' => false,
                'end' => $end->format('Y-m-d H:i:s')
            ]
        );

        $question_type = QuestionType::find(6);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function if_exam_needs_confirmation_and_user_is_not_confirmed_user_can_not_see_the_states_even_if_exam_is_started()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $owner = User::factory()->create();

        $start = Carbon::now()->subMinute();
        $end = Carbon::now()->addHours(2);
        $exam = Exam::factory()->for($owner)->create(
            [
                'published' => true,
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
                'confirmation_required' => true]
        );

        $question_type = QuestionType::find(3);
        $question = Question::factory()->for($exam)->for($question_type)->create();

        $state = State::factory()->for($question)->count(5)->create();

        Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::STATE_INDEX_ROUTE, [$exam, $question]));

        $response->assertStatus(403);
    }
}
