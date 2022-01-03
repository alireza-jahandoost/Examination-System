<?php

namespace Tests\Feature\ParticipantTests;

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

class ParticipantTest extends TestCase
{
    use RefreshDatabase;

    public const EXAM_REGISTER_ROUTE = 'exams.register';
    public const INDEX_PARTICIPANT_ROUTE = 'participants.index';
    public const SHOW_PARTICIPANT_ROUTE = 'participants.show';
    public const LOGOUT_ROUTE = "authentication.logout";
    public const QUESTION_INDEX_ROUTE = 'questions.index';
    public const QUESTION_SHOW_ROUTE = 'questions.show';
    public const ACCEPT_REGISTERED_USERS_ROUTE = 'exams.accept_user';
    public const CURRENT_PARTICIPANT_ROUTE = 'participants.current';


    protected $owner = null;
    protected function create_and_publish_an_exam($exam_inputs = [])
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
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

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

    /**
    * @test
    */
    public function an_authenticated_user_can_register_in_an_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [$data['exam']]));
        $response->assertStatus(201);
        $this->assertDatabaseCount('participants', 1);
    }

    /**
    * @test
    */
    public function if_exam_did_not_published_user_can_not_register_in_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
        ]);
        $data['exam']->published = false;
        $data['exam']->save();

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [$data['exam']]));
        $response->assertStatus(403);
        $this->assertDatabaseCount('participants', 0);
    }

    /**
    * @test
    */
    public function user_can_not_register_in_finished_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        $start = Carbon::now()->subHours(3);
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [$data['exam']]));
        $response->assertStatus(403);
        $this->assertDatabaseCount('participants', 0);
    }

    /**
    * @test
    */
    public function owner_of_exam_can_not_participate_in_the_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
        ]);

        Sanctum::actingAs(
            $data['owner'],
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [$data['exam']]));
        $response->assertStatus(403);
        $this->assertDatabaseCount('participants', 0);
    }

    /**
    * @test
    */
    public function a_guest_can_not_register_in_an_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'password' => null
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [$data['exam']]));
        $response->assertStatus(401);
        $this->assertDatabaseCount('participants', 0);
    }

    /**
    * @test
    */
    public function if_exam_has_password_user_must_send_currect_password_to_it()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'password' => 'password'
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
                'password' => 'password',
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('participants', 1);
    }

    /**
    * @test
    */
    public function for_participating_in_an_exam_password_must_be_valid()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'password' => 'password'
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
                'password' => Str::repeat('a', 300),
            ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('participants', 0);
    }

    /**
    * @test
    */
    public function if_exam_has_password_and_user_didnt_set_that_it_will_be_422()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'password' => 'password'
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('participants', 0);
    }

    /**
    * @test
    */
    public function if_exam_has_password_and_user_uses_wrong_password_it_will_be_422()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'password' => 'password'
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
                'password' => '1234'
            ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('participants', 0);
    }

    /**
    * @test
    */
    public function if_password_of_exam_was_wrong_user_will_receive_a_message_and_password_field_error()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'password' => 'password'
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
                'password' => '1234'
            ]);
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'password'
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_exam_has_password_and_user_didnt_set_any_user_will_receive_a_message_and_password_field_error()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'password' => 'password'
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'password'
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_see_the_questions_after_start_of_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(201);
        $questions_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_INDEX_ROUTE, [$data['exam']]));
        $questions_response->assertStatus(200);
        $questions_response->assertJsonStructure([
             'data' => [
                 'questions' => [
                     [
                         'question_id',
                         'question_link',
                     ]
                 ]
             ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_not_see_the_questions_before_the_start_of_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(201);
        $questions_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_INDEX_ROUTE, [$data['exam']]));
        $questions_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function if_user_didnt_participate_in_exam_can_not_see_the_questions_after_start()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $questions_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_INDEX_ROUTE, [$data['exam']]));
        $questions_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function if_exam_started_but_didnt_published_user_must_not_index_the_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $owner = User::factory()->create();
        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'published' => false,
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Participant::factory()->for($user)->for($exam)->create();

        $questions_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_INDEX_ROUTE, [$exam]));
        $questions_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function if_exam_needs_confirmation_and_user_did_not_confirmed_user_can_not_see_questions_after_the_start_of_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(201);

        $questions_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_INDEX_ROUTE, [$data['exam']]));
        $questions_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function if_user_did_not_participate_in_exam_he_can_not_show_the_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $owner = User::factory()->create();
        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'published' => true,
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $descriptive = QuestionType::find(1);

        $questions = Question::factory()->for($descriptive)->for($exam)->count(3)->create();

        $response = $this->withHeaders(['accept' => 'application/json'])->get(route(self::QUESTION_SHOW_ROUTE, [$exam, $questions[0]]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function registred_user_in_exam_can_show_questions_when_exam_is_started()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $owner = User::factory()->create();
        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'published' => true,
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Participant::factory()->for($user)->for($exam)->create();

        $descriptive = QuestionType::find(1);

        $questions = Question::factory()->for($descriptive)->for($exam)->count(3)->create();

        $response = $this->withHeaders(['accept' => 'application/json'])->get(route(self::QUESTION_SHOW_ROUTE, [$exam, $questions[0]]));
        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function if_exam_is_not_started_registred_user_can_not_show_the_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $owner = User::factory()->create();
        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'published' => true,
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Participant::factory()->for($user)->for($exam)->create();

        $descriptive = QuestionType::find(1);

        $questions = Question::factory()->for($descriptive)->for($exam)->count(3)->create();

        $response = $this->withHeaders(['accept' => 'application/json'])->get(route(self::QUESTION_SHOW_ROUTE, [$exam, $questions[0]]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function if_exam_needs_confirmation_user_needs_to_be_confirmed_to_show_the_questions_when_exam_is_started()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $owner = User::factory()->create();
        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'published' => true,
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $descriptive = QuestionType::find(1);

        $questions = Question::factory()->for($descriptive)->for($exam)->count(3)->create();

        $response = $this->withHeaders(['accept' => 'application/json'])->get(route(self::QUESTION_SHOW_ROUTE, [$exam, $questions[0]]));
        $response->assertStatus(403);

        $participant->is_accepted = true;
        $participant->save();

        $response = $this->withHeaders(['accept' => 'application/json'])->get(route(self::QUESTION_SHOW_ROUTE, [$exam, $questions[0]]));
        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function registred_user_can_show_the_questions_when_exam_is_started_if_exam_is_published()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $owner = User::factory()->create();
        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'published' => false,
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $descriptive = QuestionType::find(1);

        $questions = Question::factory()->for($descriptive)->for($exam)->count(3)->create();

        $response = $this->withHeaders(['accept' => 'application/json'])->get(route(self::QUESTION_SHOW_ROUTE, [$exam, $questions[0]]));
        $response->assertStatus(403);

        $exam->published = true;
        $exam->save();

        $response = $this->withHeaders(['accept' => 'application/json'])->get(route(self::QUESTION_SHOW_ROUTE, [$exam, $questions[0]]));
        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function owner_of_exam_can_confirm_users_to_participate_in_exams_before_start_of_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Participant::factory()->for($data['exam'])->for($user)->create();
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);

        Sanctum::actingAs($data['owner'], ['*']);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
            'user_id' => $user->id
        ]);
        $response->assertStatus(202);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => true
        ]);
    }

    /**
    * @test
    */
    public function owner_of_exam_can_confirm_users_to_participate_in_exams_when_exam_is_running()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Participant::factory()->for($data['exam'])->for($user)->create();
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);

        Sanctum::actingAs($data['owner'], ['*']);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
            'user_id' => $user->id
        ]);
        $response->assertStatus(202);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => true
        ]);
    }

    /**
    * @test
    */
    public function owner_of_exam_can_not_confirm_users_to_participate_in_exams_when_exam_is_finished()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHours(3);
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Participant::factory()->for($data['exam'])->for($user)->create();
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);

        Sanctum::actingAs($data['owner'], ['*']);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
            'user_id' => $user->id
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);
    }

    /**
    * @test
    */
    public function just_owner_of_exam_can_confirm_users_to_participate_in_exams()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);

        Sanctum::actingAs(User::factory()->create(), ['*']);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
            'user_id' => $user->id
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);
    }

    /**
    * @test
    */
    public function owner_of_exam_can_not_confirm_users_if_exam_dont_need_confirmation()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
            'user_id' => $user->id
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);
    }

    /**
    * @test
    */
    public function a_guest_can_not_confirm_any_user_for_any_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::LOGOUT_ROUTE));

        $this->app->get('auth')->forgetGuards();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
            'user_id' => $user->id
        ]);
        $response->assertStatus(401);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);
    }

    /**
    * @test
    */
    public function user_can_see_questions_of_confirmation_required_exams_after_start_after_confirm()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);

        Sanctum::actingAs($data['owner'], ['*']);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
            'user_id' => $user->id
        ]);
        $response->assertStatus(202);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => true
        ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_INDEX_ROUTE, [$data['exam']]));
        $response->assertStatus(200);
        $response->assertJsonStructure([
             'data' => [
                 'questions' => [
                     [
                         'question_id',
                         'question_link'
                     ]
                 ]
             ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_not_see_questions_of_confirmation_required_exams_before_start_after_confirm()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [
                $data['exam'],
            ]), [
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => false
        ]);

        Sanctum::actingAs($data['owner'], ['*']);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
            'user_id' => $user->id
        ]);
        $response->assertStatus(202);
        $this->assertDatabaseHas('participants', [
            'is_accepted' => true
        ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_INDEX_ROUTE, [$data['exam']]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function owner_of_exam_can_index_all_of_the_participants()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);

            Sanctum::actingAs($data['owner'], ['*']);
            $response = $this->withHeaders([
                'Accept' => 'application/json'
                ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
                'user_id' => $user->id
            ]);
            $response->assertStatus(202);
        }
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);
        }

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::INDEX_PARTICIPANT_ROUTE, [$data['exam']]));
        $response->assertStatus(200);

        $iterator = 1;
        foreach ($response->json()['data']['participants'] as $current) {
            if ($iterator === $current['participant_id']) {
                $iterator ++;
            }
        }
        $this->assertTrue($iterator === 11);
    }

    /**
    * @test
    */
    public function owner_can_get_the_participants_paginated()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);

            Sanctum::actingAs($data['owner'], ['*']);
            $response = $this->withHeaders([
                'Accept' => 'application/json'
                ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
                'user_id' => $user->id
            ]);
            $response->assertStatus(202);
        }
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);
        }

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::INDEX_PARTICIPANT_ROUTE, [$data['exam']]));
        $response->assertStatus(200);

        $iterator = 1;
        foreach ($response->json()['data']['participants'] as $current) {
            if ($iterator === $current['participant_id']) {
                $iterator ++;
            }
        }
        $this->assertTrue($iterator === 11);
        $response->assertJsonStructure([
            'data',
            'links' => [
                'first',
                'last',
            ],
            'meta' => [
                'current_page',
                'from',
            ]
        ]);
    }

    /**
    * @test
    */
    public function participants_information_must_be_wrapped_in_participants_array()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);

            Sanctum::actingAs($data['owner'], ['*']);
            $response = $this->withHeaders([
                'Accept' => 'application/json'
                ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
                'user_id' => $user->id
            ]);
            $response->assertStatus(202);
        }
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);
        }

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::INDEX_PARTICIPANT_ROUTE, [$data['exam']]));
        $response->assertStatus(200);

        $iterator = 1;
        foreach ($response->json()['data']['participants'] as $current) {
            if ($iterator === $current['participant_id']) {
                $iterator ++;
            }
        }
        $this->assertTrue($iterator === 11);
        $participant_id = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
            ])->first()->id;

        // dd($response->json());
        $response->assertJsonStructure([
            'data' => [
                'participants' => [
                    [
                        'participant_id' ,
                        'user_id',
                        'exam_id',
                        'user_link',
                        'exam_link',
                        'confirmed' ,
                    ]
                ]
            ],
        ]);
    }

    /**
    * @test
    */
    public function user_can_not_index_participants_of_another_users_exams()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);

            Sanctum::actingAs($data['owner'], ['*']);
            $response = $this->withHeaders([
                'Accept' => 'application/json'
                ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
                'user_id' => $user->id
            ]);
            $response->assertStatus(202);
        }
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);
        }

        Sanctum::actingAs(User::factory()->create(), ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::INDEX_PARTICIPANT_ROUTE, [$data['exam']]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function guest_user_can_not_index_participants_of_another_users_exams()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);

            Sanctum::actingAs($data['owner'], ['*']);
            $response = $this->withHeaders([
                'Accept' => 'application/json'
                ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$data['exam']]), [
                'user_id' => $user->id
            ]);
            $response->assertStatus(202);
        }
        for ($i = 0; $i<5; $i++) {
            Sanctum::actingAs(
                $user = User::factory()->create(),
                ['*']
            );

            $response = $this->withHeaders([
                'Accept' => 'application/json',
                ])->post(route(self::EXAM_REGISTER_ROUTE, [
                    $data['exam'],
                ]), [
                ]);

            $response->assertStatus(201);
        }

        $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGOUT_ROUTE));

        $this->app->get('auth')->forgetGuards();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::INDEX_PARTICIPANT_ROUTE, [$data['exam']]));
        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function user_can_show_his_participants_of_his_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        $user = User::factory()->create();

        $first_participant = Participant::factory()->for($user)->for($data['exam'])->create([
            'is_accepted' => false,
        ]);
        $second_participant = Participant::factory()->for($user)->for($data['exam'])->create([
            'is_accepted' => true,
        ]);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::SHOW_PARTICIPANT_ROUTE, [$data['exam'], $first_participant]));
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'participant' => [
                    'participant_id' => $first_participant->id,
                    'user_id' => $user->id,
                    'exam_id' => $data['exam']->id,
                    'user_link' => route('users.show', [$user]),
                    'exam_link' => route('exams.show', [$data['exam']]),
                    'confirmed' => false,
                ]
            ]
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::SHOW_PARTICIPANT_ROUTE, [$data['exam'], $second_participant]));
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'participant' => [
                    'participant_id' => $second_participant->id,
                    'user_id' => $user->id,
                    'exam_id' => $data['exam']->id,
                    'user_link' => route('users.show', [$user]),
                    'exam_link' => route('exams.show', [$data['exam']]),
                    'confirmed' => true,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_not_show_another_users_participants_exams()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        $user = User::factory()->create();

        $first_participant = Participant::factory()->for($user)->for($data['exam'])->create([
            'is_accepted' => false,
        ]);
        $second_participant = Participant::factory()->for($user)->for($data['exam'])->create([
            'is_accepted' => true,
        ]);

        Sanctum::actingAs(User::factory()->create(), ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::SHOW_PARTICIPANT_ROUTE, [$data['exam'], $first_participant]));
        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::SHOW_PARTICIPANT_ROUTE, [$data['exam'], $second_participant]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function for_showing_participant_its_exam_id_have_to_match_with_id_of_its_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        $user = User::factory()->create();
        $data2= $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);


        $first_participant = Participant::factory()->for($user)->for($data2['exam'])->create([
            'is_accepted' => false,
        ]);
        $second_participant = Participant::factory()->for($user)->for($data2['exam'])->create([
            'is_accepted' => true,
        ]);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::SHOW_PARTICIPANT_ROUTE, [$data['exam'], $first_participant]));
        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::SHOW_PARTICIPANT_ROUTE, [$data['exam'], $second_participant]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function a_guest_user_can_not_show_any_participant()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        $user = User::factory()->create();
        $data2= $this->create_and_publish_an_exam([
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);


        $first_participant = Participant::factory()->for($user)->for($data2['exam'])->create([
            'is_accepted' => false,
        ]);
        $second_participant = Participant::factory()->for($user)->for($data2['exam'])->create([
            'is_accepted' => true,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::SHOW_PARTICIPANT_ROUTE, [$data['exam'], $first_participant]));
        $response->assertStatus(401);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::SHOW_PARTICIPANT_ROUTE, [$data['exam'], $second_participant]));
        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function an_authenticated_user_can_not_register_in_an_exam_twice()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
        ]);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [$data['exam']]));
        $response->assertStatus(201);
        $this->assertDatabaseCount('participants', 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [$data['exam']]));
        $response->assertStatus(403);
        $this->assertDatabaseCount('participants', 1);
    }

    /**
    * @test
    */
    public function if_user_is_not_authenticated_the_current_participant_route_must_return_401_error()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::CURRENT_PARTICIPANT_ROUTE, [$data['exam']]));
        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function if_user_is_authenticated_but_is_not_registered_to_that_exam_the_current_participant_route_must_return_404()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
        ]);

        Sanctum::actingAs($user = User::factory()->create());

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::CURRENT_PARTICIPANT_ROUTE, [$data['exam']]));
        $response->assertStatus(404);
    }

    /**
    * @test
    */
    public function if_user_is_authenticated_and_registered_to_the_exam_the_participant_information_must_be_returned()
    {
        $this->seed(QuestionTypeSeeder::class);

        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
        ]);

        Sanctum::actingAs($user = User::factory()->create());

        Participant::factory()->for($data['exam'])->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->get(route(self::CURRENT_PARTICIPANT_ROUTE, [$data['exam']]));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'participant'
            ]
        ]);
    }
}
