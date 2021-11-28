<?php

namespace Tests\Feature\AnswerTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Carbon\Carbon;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

use Laravel\Sanctum\Sanctum;

use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Participant;
use App\Models\State;

use Database\Seeders\QuestionTypeSeeder;

class AnswerTest extends TestCase
{
    use RefreshDatabase;

    public const ACCEPT_REGISTERED_USERS_ROUTE = 'exams.accept_user';
    public const LOGOUT_ROUTE = "authentication.logout";
    public const EXAM_REGISTER_ROUTE = 'exams.register';
    public const CREATE_ANSWER_ROUTE = 'answers.store';
    public const INDEX_ANSWER_ROUTE = 'answers.index';
    public const DELETE_ANSWER_ROUTE = 'answers.destroy';

    protected $owner = null;
    protected function create_and_publish_an_exam($exam_inputs = [], $type_of_question = 1)
    {
        if ($this->owner === null) {
            $this->owner = User::factory()->create();
        }
        Sanctum::actingAs(
            $this->owner,
            ['*']
        );
        $exam = Exam::factory()->for($this->owner)->create(array_merge([
            'total_score' => 100
        ], $exam_inputs));
        $exam->published = true;
        $exam->save();
        if (isset($exam_inputs['password'])) {
            $exam->password = $exam_inputs['password'];
            $exam->save();
        }
        $question_type = QuestionType::find($type_of_question);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        switch ($type_of_question) {
            case 2:
                foreach ($questions as $question) {
                    $question->states()->create([
                        'text_answer' => 'test test',
                    ]);
                }
                // no break
            case 3:
            case 4:
                foreach ($questions as $question) {
                    for ($i = 0;$i < 3; $i ++) {
                        $question->states()->create([
                            'text_answer' => 'test',
                            'integer_answer' => 0,
                        ]);
                    }
                    $question->states()->create([
                        'text_answer' => 'test',
                        'integer_answer' => 1,
                    ]);
                }
                break;

            case 5:
                foreach ($questions as $question) {
                    $question->states()->create([
                        'integer_answer' => 1
                    ]);
                }
                break;

            case 6:
                foreach ($questions as $question) {
                    for ($i = 1;$i < 5; $i ++) {
                        $question->states()->create([
                            'text_answer' => 'test',
                            'integer_answer' => $i,
                        ]);
                    }
                }
                break;

        }

        $this->app->get('auth')->forgetGuards();

        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        return [
            'owner' => $this->owner,
            'exam' => $exam,
            'questions' => $questions
        ];
    }

    protected function register_user($user, $exam, $confirm_user = false)
    {
        $participant = Participant::factory()->for($user)->for($exam)->create();

        if ($confirm_user) {
            $participant->is_accepted = true;
            $participant->save();
        }
        return $participant;
    }

    /**
    * @test
    */
    public function participant_can_create_an_answer_for_started_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function participant_can_not_create_an_answer_if_exam_finished()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHours(3);
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        Participant::factory()->for($user)->for($data['exam'])->create([
            'is_accepted' => false,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function participant_can_create_answer_for_exam_if_the_status_of_participant_is_0()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::now()->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);
        $user = User::factory()->create();

        $participant = Participant::factory()->for($user)->for($data['exam'])->create([
            'is_accepted' => false,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);

        $participant->status = 1;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 2,
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 1);

        $participant->status = 2;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 3,
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 1);

        $participant->status = 3;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 4,
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function participant_can_not_create_an_answer_for_started_exam_that_didnt_registered_into()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam']);
        // $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function participant_can_not_create_an_answer_for_started_exam_that_didnt_confirmed()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            // 'confirmation_required' => false,
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function participant_can_create_an_answer_for_started_exam_that_confirmed()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            // 'confirmation_required' => false,
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        $this->register_user($user, $data['exam'], true);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function integer_answer_field_must_have_valid_value()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);
        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 'test',
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 3,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function text_answer_field_must_have_valid_value()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => Str::repeat('a', 40001),
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => Str::repeat('a', 40000),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function participant_can_not_create_an_answer_if_exam_did_not_started_yet()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function participant_can_not_create_an_answer_if_exam_did_not_started_yet_even_if_he_is_confirmed()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            // 'confirmation_required' => false,
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        $this->register_user($user, $data['exam'], true);
        // $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function text_answer_and_integer_answer_can_not_be_null_together()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [

        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function descriptive_questions_just_have_text_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 123,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function fill_the_blank_questions_just_have_text_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 2);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 123,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function multiple_questions_just_have_integer_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 2,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function multiple_questions_integer_part_must_have_a_state_with_that_id()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 100,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function multiple_questions_integer_part_must_have_a_state_with_that_id_that_belongs_to_that_question()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 10,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function select_questions_just_have_integer_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 2,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function select_questions_integer_part_must_have_a_state_with_that_id()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 100,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function select_questions_integer_part_must_have_a_state_with_that_id_that_belongs_to_that_question()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 10,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function true_or_false_questions_just_have_integer_answer_1_or_0()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 5);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 2,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1000,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
        $data['questions'][0]->answers()->delete();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 0,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function ordering_questions_must_just_have_integer_parts()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 6);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 2,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 2);
    }

    /**
    * @test
    */
    public function descriptive_question_can_just_have_1_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test test',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function fill_the_blank_question_can_just_have_1_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 2);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test test',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function multiple_question_can_not_have_more_answers_than_states_of_question()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 2,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 2);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 3,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 3);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 4,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 4);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 4);
    }

    /**
    * @test
    */
    public function select_question_can_just_have_1_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 2,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function true_or_false_question_can_just_have_1_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 5);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 0,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function ordering_question_can_not_have_more_answers_than_states_of_question()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 6);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 2,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 2);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 3,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 3);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 4,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 4);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 4);
    }

    /**
    * @test
    */
    public function multiple_questions_can_not_have_repeated_answers()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function ordering_questions_can_not_have_repeated_answers()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 6);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'integer_part' => 1,
        ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function user_can_get_the_answers_of_the_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data['questions'][0], $participant]));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'answers' => [
                    [
                        'text_part',
                        'integer_part',
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function answer_response_do_not_have_grade()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data['questions'][0], $participant]));
        $response->assertStatus(200);
        $response->assertJsonMissing([
            'data' => [
                'answers' => [
                    [
                        'grade' => null
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function every_user_just_can_take_his_answers()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($anotherUser, $data['exam'], false);
        $this->assertDatabaseCount('participants', 2);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        Sanctum::actingAs($anotherUser, ['*']);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data['questions'][0], $participant]));
        $response->assertStatus(403);

        Sanctum::actingAs($user, ['*']);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data['questions'][0], $participant]));
        $response->assertStatus(200);
        $response->assertExactJson([
            'data' => [
                'answers' => [
                    [
                        'text_part' => 'test',
                        'integer_part' => null,
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_must_register_in_the_exam_to_index_his_answers()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        Sanctum::actingAs($anotherUser, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data['questions'][0], $participant]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function guest_user_can_not_get_any_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $logout = $this->withHeaders([
            'Accept' => 'application/json'
        ])->post(route(self::LOGOUT_ROUTE));

        $this->app->get('auth')->forgetGuards();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data['questions'][0], $participant]));
        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function user_can_not_index_any_answer_before_the_start_of_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data['questions'][0], $participant]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function user_can_not_index_answers_if_exam_needs_confirmation_and_he_did_not_confirmed()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            // 'confirmation_required' => false,
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data['questions'][0], $participant]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function user_can_index_answers_if_exam_needs_confirmation_and_he_confirmed()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            // 'confirmation_required' => false,
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        $this->register_user($user, $data['exam'], true);
        // $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data['questions'][0], $participant]));
        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function for_indexing_answers_question_and_participant_must_be_related_to_each_other()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            // 'confirmation_required' => false,
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();
        $data2 = $this->create_and_publish_an_exam([
            // 'confirmation_required' => false,
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam'], true);
        // $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::INDEX_ANSWER_ROUTE, [$data2['questions'][0], $participant]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function user_can_delete_his_answers()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(202);
        $this->assertDatabaseCount('answers', 0);
    }

    /**
    * @test
    */
    public function if_the_status_of_participant_is_not_0_participant_can_not_delete_his_answers()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $participant = $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $this->assertDatabaseCount('answers', 1);

        $participant->status = 1;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 1);

        $participant->status = 2;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 1);

        $participant->status = 3;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(403);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function when_deleting_answers_just_users_answers_will_be_deleted()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($anotherUser, $data['exam'], false);
        $this->assertDatabaseCount('participants', 2);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);
        $this->assertDatabaseCount('answers', 1);

        Sanctum::actingAs($anotherUser, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(202);
        $this->assertDatabaseCount('answers', 1);
    }

    /**
    * @test
    */
    public function user_must_be_participating_in_the_exam_to_delete_answers()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        // $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 0);

        Sanctum::actingAs($user, ['*']);
        //
        // $response = $this->withHeaders([
        //     'Accept' => 'application/json',
        // ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
        //     'text_part' => 'test',
        // ]);
        //
        // $this->assertDatabaseCount('answers', 1);
        //
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function exam_must_be_started_if_user_want_to_delete_answers()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);
        //
        // $response = $this->withHeaders([
        //     'Accept' => 'application/json',
        // ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
        //     'text_part' => 'test',
        // ]);
        //
        // $this->assertDatabaseCount('answers', 1);
        //
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function participant_can_not_delete_answers_if_exam_finished()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHours(3);
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);
        //
        // $response = $this->withHeaders([
        //     'Accept' => 'application/json',
        // ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
        //     'text_part' => 'test',
        // ]);
        //
        // $this->assertDatabaseCount('answers', 1);
        //
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function participant_can_not_delete_answers_if_exam_needs_confirmation_and_user_is_not_nofirmed()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            // 'confirmation_required' => false,
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);
        //
        // $response = $this->withHeaders([
        //     'Accept' => 'application/json',
        // ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
        //     'text_part' => 'test',
        // ]);
        //
        // $this->assertDatabaseCount('answers', 1);
        //
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function guest_user_can_not_delete_answers()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $this->assertDatabaseCount('answers', 1);

        $logout = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGOUT_ROUTE));

        $this->app->get('auth')->forgetGuards();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function user_can_create_answers_again_after_deleting_them()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            // 'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $user = User::factory()->create();

        // $this->register_user($user, $data['exam'], true);
        $this->register_user($user, $data['exam'], false);
        $this->assertDatabaseCount('participants', 1);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $this->assertDatabaseCount('answers', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->delete(route(self::DELETE_ANSWER_ROUTE, $data['questions'][0]));
        $response->assertStatus(202);
        $this->assertDatabaseCount('answers', 0);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::CREATE_ANSWER_ROUTE, [$data['questions'][0]]), [
            'text_part' => 'test',
        ]);

        $this->assertDatabaseCount('answers', 1);
    }
}
