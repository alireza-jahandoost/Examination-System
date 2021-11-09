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

class FinishExamTest extends TestCase
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
    public function user_can_finish_his_exam()
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
    }

    /**
    * @test
    */
    public function if_user_did_not_participating_in_the_exam_he_can_not_finish_any_exam()
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

        // $this->register_user($user, $data['exam']);
        // $this->assertDatabaseCount('participants', 1);

        // foreach($data['questions'] as $question){
        //     $this->send_answer_for_user($user, $question);
        // }

        // $this->assertDatabaseMissing('participants', [
        //     'status' => 1,
        // ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(403);
        // $this->assertDatabaseHas('participants', [
        //     'status' => 1,
        // ]);

        Queue::assertPushed(CorrectExamJob::class, 0);
    }

    /**
    * @test
    */
    public function if_exam_has_been_finished_user_can_not_finish_any_exam()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHours(3);
        // $start = Carbon::now()->subHour();
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

        // foreach($data['questions'] as $question){
        //     $this->send_answer_for_user($user, $question);
        // }

        // $this->assertDatabaseMissing('participants', [
        //     'status' => 1,
        // ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(403);
        // $this->assertDatabaseHas('participants', [
        //     'status' => 1,
        // ]);

        Queue::assertPushed(CorrectExamJob::class, 0);
    }

    /**
    * @test
    */
    public function if_exam_is_not_started_yet_participant_can_not_finish_that()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->addHour();
        // $start = Carbon::now()->subHour();
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

        // foreach($data['questions'] as $question){
        //     $this->send_answer_for_user($user, $question);
        // }

        // $this->assertDatabaseMissing('participants', [
        //     'status' => 1,
        // ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(403);
        // $this->assertDatabaseHas('participants', [
        //     'status' => 1,
        // ]);

        Queue::assertPushed(CorrectExamJob::class, 0);
    }

    /**
    * @test
    */
    public function if_exam_needs_confirmation_and_user_did_not_confirmed_he_can_not_finish_any_exam()
    {
        Queue::fake();
        $this->seed(QuestionTypeSeeder::class);
        $start = Carbon::now()->subHour();
        $end = Carbon::make($start)->addHours(2);
        $data = $this->create_and_publish_an_exam([
            // 'confirmation_required' => false,
            'confirmation_required' => true,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        $user = User::factory()->create();

        Participant::factory()->for($user)->for($data['exam'])->create([
            'is_accepted' => false,
        ]);

        // foreach($data['questions'] as $question){
        //     $this->send_answer_for_user($user, $question);
        // }

        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::FINISH_EXAM_ROUTE, [$data['exam']]));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('participants', [
            'status' => 1,
        ]);

        Queue::assertPushed(CorrectExamJob::class, 0);
    }
}
