<?php

namespace Tests\Feature\AnswerTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Carbon\Carbon;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;

use Laravel\Sanctum\Sanctum;

use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionGrade;
use App\Models\QuestionType;
use App\Models\Participant;
use App\Models\State;

use Database\Seeders\QuestionTypeSeeder;

use App\Jobs\CorrectExamJob;

class CorrectAnswersTest extends TestCase
{
    use RefreshDatabase;


    public const LOGOUT_ROUTE = "authentication.logout";
    public const ACCEPT_REGISTERED_USERS_ROUTE = 'exams.accept_user';
    public const EXAM_REGISTER_ROUTE = 'exams.register';
    public const CREATE_ANSWER_ROUTE = 'answers.store';
    public const FINISH_EXAM_ROUTE = 'participants.finish_exam';
    public const PARTICIPANT_INDEX_ROUTE = 'participants.index';
    public const PARTICIPANT_SHOW_ROUTE = 'participants.show';
    public const ANSWER_INDEX_ROUTE = 'answers.index';
    public const SUBMIT_GRADE_ROUTE = 'participants.save_score';
    public const GET_QUESTION_GRADE_ROUTE = 'participants.grade.question';


    protected $owner = null;
    protected function create_and_publish_an_exam($exam_inputs = [], $type_of_question = 1, $includes_descriptive = false)
    {
        if ($this->owner === null) {
            $this->owner = User::factory()->create();
        }
        Sanctum::actingAs(
            $this->owner,
            ['*']
        );
        $exam = Exam::factory()->for($this->owner)->create(array_merge([
            'total_score' => ($includes_descriptive ? 120 : 100)
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

        if ($includes_descriptive) {
            $descriptive = QuestionType::find(1);
            $questions[] = Question::factory()->for($exam)->for($descriptive)->create([
                'score' => 20,
            ]);
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
        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::EXAM_REGISTER_ROUTE, [$exam]));
        $response->assertStatus(201);

        if ($confirm_user) {
            Sanctum::actingAs($this->owner, ['*']);
            $response = $this->withHeaders([
                'Accept' => 'application/json'
                ])->put(route(self::ACCEPT_REGISTERED_USERS_ROUTE, [$exam]), [
                'user_id' => $user->id
            ]);
            $response->assertStatus(202);
            $this->assertDatabaseHas('participants', [
                'is_accepted' => true
            ]);
        }

        $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGOUT_ROUTE));
        $this->app->get('auth')->forgetGuards();
    }

    protected function send_answer_for_user($user, $question, $correct = false)
    {
        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $question->exam_id,
        ])->first();

        Sanctum::actingAs($user, ['*']);

        $return_value;
        switch ($question->questionType->id) {
            case 1:
                $response = $this->withHeaders([
                    'Accept' => 'application/json',
                ])->post(route(self::CREATE_ANSWER_ROUTE, [$question]), [
                    'text_part' => 'test',
                ]);
                $return_value = 'test';
                $response->assertStatus(201);
                break;

            case 2:
                $answer = State::where([
                    'question_id' => $question->id,
                ])->first()->text_answer;
                $response = $this->withHeaders([
                    'Accept' => 'application/json',
                ])->post(route(self::CREATE_ANSWER_ROUTE, [$question]), [
                    'text_part' => $correct ? $answer : $answer.'a',
                ]);
                $return_value = $answer;
                $response->assertStatus(201);
                break;

            case 3:
                $correct_answers = State::where([
                    'question_id' => $question->id,
                    'integer_answer' => 1,
                ])->pluck('id');
                $wrong_answers = State::where([
                    'question_id' => $question->id,
                    'integer_answer' => 0,
                ])->pluck('id');

                $answers = $correct ? $correct_answers : $wrong_answers;
                foreach ($answers as $answer) {
                    $response = $this->withHeaders([
                        'Accept' => 'application/json',
                    ])->post(route(self::CREATE_ANSWER_ROUTE, [$question]), [
                        'integer_part' => $answer
                    ]);
                    $response->assertStatus(201);
                }
                $return_value = $answers;
                break;

            case 4:
                $correct_answer = State::where([
                    'question_id' => $question->id,
                    'integer_answer' => 1,
                ])->first()->id;
                $wrong_answer = State::where([
                    'question_id' => $question->id,
                    'integer_answer' => 0,
                ])->first()->id;

                $answer = $correct ? $correct_answer : $wrong_answer;
                $response = $this->withHeaders([
                    'Accept' => 'application/json',
                ])->post(route(self::CREATE_ANSWER_ROUTE, [$question]), [
                    'integer_part' => $answer
                ]);
                $return_value = $answer;
                $response->assertStatus(201);
                break;

            case 5:
                $correct_answer = State::where([
                    'question_id' => $question->id,
                ])->first()->integer_answer;
                $wrong_answer = $correct_answer ^ 1;

                $answer = $correct ? $correct_answer : $wrong_answer;
                $response = $this->withHeaders([
                    'Accept' => 'application/json',
                ])->post(route(self::CREATE_ANSWER_ROUTE, [$question]), [
                    'integer_part' => $answer
                ]);
                $response->assertStatus(201);
                $return_value = $answer;
                break;

            case 6:
                $answers = State::where([
                    'question_id' => $question->id,
                ]);
                if ($correct) {
                    $answers = $answers->orderBy('integer_answer')->get();
                } else {
                    $answers = $answers->orderByDesc('integer_answer')->get();
                }
                foreach ($answers as $answer) {
                    $response = $this->withHeaders([
                        'Accept' => 'application/json',
                    ])->post(route(self::CREATE_ANSWER_ROUTE, [$question]), [
                        'integer_part' => $answer->id,
                    ]);
                    $response->assertStatus(201);
                }
                $return_value = $answers;
                break;
            default:
                dd('here is a problem(send_answer_to_user)');
                break;
        }

        $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGOUT_ROUTE));
        $this->app->get('auth')->forgetGuards();

        return $return_value;
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_must_be_queued_in_the_queue()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        foreach ($data['questions'] as $question) {
            $this->send_answer_for_user($user, $question);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('participants', [
            'status' => 1,
        ]);

        Queue::assertPushed(CorrectExamJob::class);
    }

    /**
    * @test
    */
    public function CorrectExam_job_must_be_pushed_once()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        foreach ($data['questions'] as $question) {
            $this->send_answer_for_user($user, $question);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('participants', [
            'status' => 1,
        ]);

        Queue::assertPushed(CorrectExamJob::class, 1);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_select()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_select_when_there_is_more_than_one_participant()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->register_user($user1, $data['exam']);
        $this->register_user($user2, $data['exam']);
        $this->assertDatabaseCount('participants', 2);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user1, $question, $i % 2);
            $this->send_answer_for_user($user2, $question, 0);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user1, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
            'user_id' => $user1->id,
        ]);

        Sanctum::actingAs($user2, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 0,
            'status' => 3,
            'user_id' => $user2->id,
        ]);
    }

    /**
    * @test
    */
    public function participant_dont_have_to_answer_all_the_selecting_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_fill_the_blank()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 2);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_fill_the_blank_when_there_is_more_than_one_participant()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 2);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->register_user($user1, $data['exam']);
        $this->register_user($user2, $data['exam']);
        $this->assertDatabaseCount('participants', 2);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user1, $question, $i % 2);
            $this->send_answer_for_user($user2, $question, 0);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user1, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
            'user_id' => $user1->id,
        ]);

        Sanctum::actingAs($user2, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 0,
            'status' => 3,
            'user_id' => $user2->id,
        ]);
    }

    /**
    * @test
    */
    public function participant_dont_have_to_answer_all_the_fill_the_blank_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 2);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_multiple_answer()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_multiple_answer_when_there_is_more_than_one_participant()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->register_user($user1, $data['exam']);
        $this->register_user($user2, $data['exam']);
        $this->assertDatabaseCount('participants', 2);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user1, $question, $i % 2);
            $this->send_answer_for_user($user2, $question, 0);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user1, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
            'user_id' => $user1->id,
        ]);

        Sanctum::actingAs($user2, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 0,
            'status' => 3,
            'user_id' => $user2->id,
        ]);
    }

    /**
    * @test
    */
    public function participant_dont_have_to_answer_all_the_multiple_answer_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_true_or_false()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 5);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_true_or_false_when_there_is_more_than_one_participant()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 5);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->register_user($user1, $data['exam']);
        $this->register_user($user2, $data['exam']);
        $this->assertDatabaseCount('participants', 2);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user1, $question, $i % 2);
            $this->send_answer_for_user($user2, $question, 0);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user1, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
            'user_id' => $user1->id,
        ]);

        Sanctum::actingAs($user2, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 0,
            'status' => 3,
            'user_id' => $user2->id,
        ]);
    }

    /**
    * @test
    */
    public function participant_dont_have_to_answer_all_the_true_or_false_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 5);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_ordering()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 6);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function after_finishing_the_exam_CorrectExam_job_can_correct_the_answers_of_the_user_with_type_of_ordering_when_there_is_more_than_one_participant()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 6);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->register_user($user1, $data['exam']);
        $this->register_user($user2, $data['exam']);
        $this->assertDatabaseCount('participants', 2);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user1, $question, $i % 2);
            $this->send_answer_for_user($user2, $question, 0);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user1, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
            'user_id' => $user1->id,
        ]);

        Sanctum::actingAs($user2, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 0,
            'status' => 3,
            'user_id' => $user2->id,
        ]);
    }

    /**
    * @test
    */
    public function participant_dont_have_to_answer_all_the_ordering_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 6);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function if_exam_includes_descriptive_questions_after_correcting_status_must_be_2()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
            'status' => 2,
        ]);
    }

    /**
    * @test
    */
    public function owner_of_exam_can_see_status_and_grade_of_participants()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_INDEX_ROUTE, $data['exam']));

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'participants' => [
                    [
                        'status',
                        'grade',
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function for_owner_grade_must_be_null_if_status_of_that_participant_was_not_3()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);
        // status == 0

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_INDEX_ROUTE, $data['exam']));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participants' => [
                    [
                        'grade' => null,
                    ]
                ]
            ]
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        // status == 1

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_INDEX_ROUTE, $data['exam']));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participants' => [
                    [
                        'grade' => null,
                    ]
                ]
            ]
        ]);

        // status == 2
        $participant = Participant::first();
        $participant->status = 2;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_INDEX_ROUTE, $data['exam']));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participants' => [
                    [
                        'grade' => null,
                    ]
                ]
            ]
        ]);

        // status == 3
        $participant = Participant::first();
        $participant->status = 3;
        $participant->grade = 3;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_INDEX_ROUTE, $data['exam']));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participants' => [
                    [
                        'grade' => $participant->grade,
                    ]
                ]
            ]
        ]);
    }
    /**
    * @test
    */
    public function if_exam_is_not_finished_by_user_status_must_be_NOT_FINISHED()
    {
        Queue::fake();

        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        // $response = $this->withHeaders([
        //     'Accept' => 'application/json',
        // ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));
        //
        // $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_INDEX_ROUTE, $data['exam']));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participants' => [
                    [
                        'status' => 'NOT_FINISHED',
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_exam_finished_but_not_corrected_status_must_be_IN_PROCESSING()
    {
        Queue::fake();

        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_INDEX_ROUTE, $data['exam']));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participants' => [
                    [
                        'status' => 'IN_PROCESSING',
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_exam_corrected_but_needs_manual_correcting_status_must_be_WAIT_FOR_MANUAL_CORRECTING()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_INDEX_ROUTE, $data['exam']));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participants' => [
                    [
                        'status' => 'WAIT_FOR_MANUAL_CORRECTING',
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_correcting_the_exam_has_been_finished_status_must_be_FINISHED()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_INDEX_ROUTE, $data['exam']));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participants' => [
                    [
                        'status' => 'FINISHED',
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function exams_owner_can_see_the_answers_of_a_participant_of_specific_question()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::ANSWER_INDEX_ROUTE, [$data['questions'][0], $participant]));

        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function exams_owner_can_not_see_the_answers_of_a_participant_of_specific_question_if_participant_was_not_in_that_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);
        $data2 = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data2['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::ANSWER_INDEX_ROUTE, [$data2['questions'][0], $participant]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function exams_owner_can_score_the_descriptive_questions_for_participants()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);
    }

    /**
    * @test
    */
    public function exams_owner_can_score_the_fill_the_blank_questions_for_participants()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 2, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][0], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);
    }

    /**
    * @test
    */
    public function exams_owner_can_score_the_multiple_answer_questions_for_participants()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 3, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][0], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);
    }

    /**
    * @test
    */
    public function exams_owner_can_score_the_select_the_answer_questions_for_participants()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][0], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);
    }

    /**
    * @test
    */
    public function exams_owner_can_score_the_true_or_false_questions_for_participants()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 5, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][0], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);
    }

    /**
    * @test
    */
    public function exams_owner_can_score_the_ordering_questions_for_participants()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 6, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][0], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);
    }

    /**
    * @test
    */
    public function exam_owner_can_not_score_more_than_the_score_of_question()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]), [
            'grade' => 30,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
        ]);
    }

    /**
    * @test
    */
    public function exam_owner_can_not_score_less_than_0()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]), [
            'grade' => -5,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
        ]);
    }

    /**
    * @test
    */
    public function descriptive_question_can_be_empty()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]), [
            'grade' => 0,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
        ]);
    }

    /**
    * @test
    */
    public function after_scoring_all_manual_questions_status_must_be_3()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseHas('participants', [
            'status' => 0,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        for ($i = 0;$i < 4;$i ++) {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][$i], $participant]), [
                'grade' => 10,
            ]);

            $response->assertStatus(202);

            $this->assertDatabaseHas('participants', [
                'status' => 2,
            ]);
        }

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][4], $participant]), [
            'grade' => 10,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'status' => 3,
        ]);
    }

    /**
    * @test
    */
    public function just_owner_of_exam_can_score_the_participants()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseHas('participants', [
            'status' => 0,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        // Sanctum::actingAs($data['owner'],['*']);

        for ($i = 0;$i < 4;$i ++) {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][$i], $participant]), [
                'grade' => 10,
            ]);

            $response->assertStatus(403);

            $this->assertDatabaseHas('participants', [
                'status' => 2,
            ]);
        }

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][4], $participant]), [
            'grade' => 10,
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('participants', [
            'status' => 2,
        ]);
    }

    /**
    * @test
    */
    public function for_scoring_the_participant_participant_must_be_for_that_specific_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);
        $secondOwner = User::factory()->create();
        $secondExam = Exam::factory()->for($secondOwner)->create([
            'total_score' => 20,
        ]);
        $descriptive_question = QuestionType::find(1);
        $question = Question::factory()->for($secondExam)->for($descriptive_question)->create([
            'score' => 20,
        ]);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseHas('participants', [
            'status' => 0,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($secondOwner, ['*']);

        for ($i = 0;$i < 4;$i ++) {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::SUBMIT_GRADE_ROUTE, [$question, $participant]), [
                'grade' => 10,
            ]);

            $response->assertStatus(403);

            $this->assertDatabaseHas('participants', [
                'status' => 2,
            ]);
        }

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][4], $participant]), [
            'grade' => 10,
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('participants', [
            'status' => 2,
        ]);
    }

    /**
    * @test
    */
    public function the_status_code_of_participant_must_be_2_that_owner_can_score_the_manual_questions()
    {
        Queue::fake();

        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseHas('participants', [
            'status' => 0,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        for ($i = 0;$i < 4;$i ++) {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][$i], $participant]), [
                'grade' => 10,
            ]);

            $response->assertStatus(403);

            $this->assertDatabaseHas('participants', [
                'status' => 1,
            ]);
        }

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][4], $participant]), [
            'grade' => 10,
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('participants', [
            'status' => 1,
        ]);
    }

    /**
    * @test
    */
    public function participant_can_see_his_grade_if_status_was_equal_to_3()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_SHOW_ROUTE, [$data['exam'], $participant]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participant' => [
                    'grade' => 40,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function participants_grade_will_be_null_if_status_was_1()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_SHOW_ROUTE, [$data['exam'], $participant]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participant' => [
                    'grade' => null,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function participants_grade_will_be_null_if_the_status_code_was_2()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        // $response = $this->withHeaders([
        //     'Accept' => 'application/json',
        // ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]),[
        //     'grade' => 0,
        // ]);
        //
        // $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_SHOW_ROUTE, [$data['exam'], $participant]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participant' => [
                    'grade' => null,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_the_status_was_2_user_can_see_that_his_exams_owner_did_not_correct_the_question()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        // $response = $this->withHeaders([
        //     'Accept' => 'application/json',
        // ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]),[
        //     'grade' => 0,
        // ]);
        //
        // $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 40,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_SHOW_ROUTE, [$data['exam'], $participant]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participant' => [
                    'status' => 'WAIT_FOR_MANUAL_CORRECTING',
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_the_status_code_of_participant_was_1_user_can_see_that_its_reason_is_system_correcting()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::PARTICIPANT_SHOW_ROUTE, [$data['exam'], $participant]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'participant' => [
                    'status' => 'IN_PROCESSING',
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function owner_can_get_the_grade_of_participant_of_an_specific_question()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][0]]));

        $response->assertStatus(200);

        Sanctum::actingAs($data['owner'], ['*']);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'participant_id' => $participant->id,
                    'question_id' => $data['questions'][0]->id,
                    'grade' => 0,
                    'participant_link' => route('participants.show', [$data['exam'], $participant]),
                    'question_link' => route('questions.show', [$data['exam'], $data['questions'][0]]),
                ]
            ]
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'participant_id' => $participant->id,
                    'question_id' => $data['questions'][1]->id,
                    'grade' => 20,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function just_owner_of_participants_exam_can_see_his_questions_grades()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][0]]));

        $response->assertStatus(403);


        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function owner_can_see_the_participant_grade_if_participant_belongs_to_that_exam()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);
        $data2 = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        Sanctum::actingAs(
            $data['owner'],
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data2['questions'][0]]));

        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function participant_can_see_his_grade_of_his_question()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);
        $data2 = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data2['questions'][0]]));

        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function user_can_not_see_his_grade_of_a_question_if_status_was_not_2_or_3()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);
        $data2 = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }
        // status == 0

        Sanctum::actingAs($user, ['*']);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data2['questions'][0]]));

        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        // status == 1

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data2['questions'][0]]));

        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(403);

        // status == 2
        $participant->status = 2;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][0]]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => null,
                ]
            ]
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => null,
                ]
            ]
        ]);

        // status == 3
        foreach (Question::all() as $question) {
            QuestionGrade::factory()->for($question)->for($participant)->create([
                'grade' => 10,
            ]);
        }
        $participant->status = 3;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][0]]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => 10,
                ]
            ]
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => 10,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function owner_of_exam_cannot_see_the_participants_grade_of_a_question_if_the_status_was_not_2_or_3()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 1);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        for ($i = 0;$i < 5;$i ++) {
            $question = $data['questions'][$i];
            $this->send_answer_for_user($user, $question, $i % 2);
        }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);
        // status == 0

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        Sanctum::actingAs(
            $data['owner'],
            ['*']
        );

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][0]]));

        $response->assertStatus(403);


        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(403);

        // status == 1

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][0]]));

        $response->assertStatus(403);


        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(403);

        // status == 2
        $participant->status = 2;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][0]]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => null,
                ]
            ]
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => null,
                ]
            ]
        ]);

        // status == 3
        foreach (Question::all() as $question) {
            QuestionGrade::factory()->for($question)->for($participant)->create([
                'grade' => 10,
            ]);
        }
        $participant->status = 3;
        $participant->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][0]]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => 10,
                ]
            ]
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][1]]));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => 10,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_see_the_grade_of_his_manual_correcting_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][5]]));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => 20,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_get_the_manual_correcting_questions_values_before_correcting_but_it_will_be_null()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][5]]));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => null,
                ]
            ]
        ]);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][5]]));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => 20,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function owner_can_get_the_grade_of_participants_of_manual_questions()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(self::GET_QUESTION_GRADE_ROUTE, [$participant, $data['questions'][5]]));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'grade' => [
                    'grade' => 20,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_owner_change_the_grade_of_a_manual_correcting_question_the_program_must_not_create_another_QuestionGrade_and_we_just_have_to_modify_that()
    {
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            'confirmation_required' => false,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ], 4, true);

        $user = User::factory()->create();

        $this->register_user($user, $data['exam']);
        $this->assertDatabaseCount('participants', 1);

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $data['exam']->id,
        ])->first();

        $this->send_answer_for_user($user, $data['questions'][0], false);
        $this->send_answer_for_user($user, $data['questions'][1], true);
        $this->send_answer_for_user($user, $data['questions'][3], true);
        $this->send_answer_for_user($user, $data['questions'][5], true);

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(202);

        Sanctum::actingAs($data['owner'], ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]), [
            'grade' => 20,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseCount('question_grades', 6);
        $this->assertDatabaseHas('question_grades', [
            'grade' => 20,
            'participant_id' => $participant->id,
            'question_id' => $data['questions'][5]->id,
        ]);
        $this->assertDatabaseHas('participants', [
            'grade' => 60,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::SUBMIT_GRADE_ROUTE, [$data['questions'][5], $participant]), [
            'grade' => 10,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('participants', [
            'grade' => 50,
        ]);
        $this->assertDatabaseCount('question_grades', 6);
        $this->assertDatabaseHas('question_grades', [
            'grade' => 10,
            'participant_id' => $participant->id,
            'question_id' => $data['questions'][5]->id,
        ]);
    }
}
