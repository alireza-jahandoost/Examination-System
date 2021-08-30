<?php

namespace Tests\Feature;

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


class PublishFeatureTest extends TestCase
{

    use RefreshDatabase;

    const PUBLISH_EXAM_ROUTE = 'exams.publish';
    const UPDATE_EXAM_ROUTE = 'exams.update';
    const QUESTION_UPDATE_ROUTE = 'questions.update';
    const QUESTION_STORE_ROUTE = 'questions.store';
    const STATE_CREATE_ROUTE = 'states.store';
    const STATE_UPDATE_ROUTE = 'states.update';
    const STATE_DELETE_ROUTE = 'states.destroy';
    const QUESTION_DELETE_ROUTE = 'questions.destroy';

    /**
    * @test
    */
    public function use_can_publish_his_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function a_guest_user_can_not_publish_any_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        $anotherUser = User::factory()->create();
        $exam = Exam::factory()->for($anotherUser)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));
        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function just_user_itself_can_publish_his_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $anotherUser = User::factory()->create();
        $exam = Exam::factory()->for($anotherUser)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));
        $response->assertStatus(403);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function if_sum_of_scores_of_exam_do_not_match_to_exams_total_score_exam_wont_publish()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(4)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function fill_the_blank_questions_must_have_atleast_one_state_to_publish()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $descriptive = QuestionType::find(1);
        $fill_be_blank = QuestionType::find(2);

        $questions = Question::factory()->count(4)->for($exam)->for($descriptive)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($fill_be_blank)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => null,
            'text_answer' => 'test'
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => null,
            'text_answer' => 'test'
        ]);

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function multiple_questions_must_have_more_than_one_state_to_publish()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $descriptive = QuestionType::find(1);
        $select = QuestionType::find(3);

        $questions = Question::factory()->count(4)->for($exam)->for($descriptive)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($select)->create([
            'score' => 20
        ]);
        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function select_questions_must_have_more_than_one_state_to_publish()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $descriptive = QuestionType::find(1);
        $multiple = QuestionType::find(4);

        $questions = Question::factory()->count(4)->for($exam)->for($descriptive)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($multiple)->create([
            'score' => 20
        ]);
        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function boolean_questions_must_have_one_state_to_publish()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $descriptive = QuestionType::find(1);
        $boolean = QuestionType::find(5);

        $questions = Question::factory()->count(4)->for($exam)->for($descriptive)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($boolean)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 1,
        ]);
        $question->states()->create([
            'integer_answer' => 0,
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->first()->delete();

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function ordering_questions_must_have_more_than_one_state_to_publish()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $descriptive = QuestionType::find(1);
        $ordering = QuestionType::find(6);

        $questions = Question::factory()->count(4)->for($exam)->for($descriptive)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($ordering)->create([
            'score' => 20
        ]);
        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 2,
            'text_answer' => 'test'
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 3,
            'text_answer' => 'test'
        ]);

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function multiple_questions_have_to_have_atleast_one_answer()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $descriptive = QuestionType::find(1);
        $multiple = QuestionType::find(3);

        $questions = Question::factory()->count(4)->for($exam)->for($descriptive)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($multiple)->create([
            'score' => 20
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);

        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function select_questions_have_to_have_atleast_one_answer()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $descriptive = QuestionType::find(1);
        $select = QuestionType::find(4);

        $questions = Question::factory()->count(4)->for($exam)->for($descriptive)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($select)->create([
            'score' => 20
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);

        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function ordering_questions_have_to_have_orders_from_1_to_number_of_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $descriptive = QuestionType::find(1);
        $ordering = QuestionType::find(6);

        $questions = Question::factory()->count(4)->for($exam)->for($descriptive)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($ordering)->create([
            'score' => 20
        ]);
        $question->states()->create([
            'integer_answer' => 3,
            'text_answer' => 'test'
        ]);
        $question->states()->create([
            'integer_answer' => 2,
            'text_answer' => 'test'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);

        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function ordering_questions_can_not_have_repeated_order()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $descriptive = QuestionType::find(1);
        $ordering = QuestionType::find(6);

        $questions = Question::factory()->count(4)->for($exam)->for($descriptive)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($ordering)->create([
            'score' => 20
        ]);
        $state = $question->states()->create([
            'integer_answer' => 2,
            'text_answer' => 'test'
        ]);
        $question->states()->create([
            'integer_answer' => 2,
            'text_answer' => 'test'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);

        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);
        $state->delete();
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function after_publishing_the_exam_exam_information_can_not_be_changed_anymore()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, [$exam]), [
                'exam_name' => 'test2',
            ]);

        $second_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function after_publishing_the_exam_questions_of_exam_can_not_be_changed_anymore()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::QUESTION_UPDATE_ROUTE, [$exam, $questions[0]]), [
                'question_text' => 'test',
            ]);

        $second_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function after_publishing_the_exam_user_can_not_create_questions_anymore()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::QUESTION_STORE_ROUTE, [$exam]), [
                 'question_text' => 'test',
                 'question_type_id' => 1,
                 'question_score' => 20,
            ]);

        $second_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function after_publishing_the_exam_user_can_not_delete_questions_anymore()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(self::QUESTION_DELETE_ROUTE, [$exam, $questions[0]]));

        $second_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function after_publishing_the_exam_user_can_not_create_states_anymore()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);
        $fill_the_blank = QuestionType::find(2);

        $questions = Question::factory()->count(4)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($fill_the_blank)->create([
            'score' => 20,
        ]);
        $question->states()->create([
            'integer_answer' => null
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test test'
            ]);

        $second_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function after_publishing_the_exam_user_can_not_update_states_anymore()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);
        $fill_the_blank = QuestionType::find(2);

        $questions = Question::factory()->count(4)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($fill_the_blank)->create([
            'score' => 20,
        ]);
        $state = $question->states()->create([
            'integer_answer' => null
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test test'
            ]);

        $second_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function after_publishing_the_exam_user_can_not_delete_states_anymore()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $question_type = QuestionType::find(1);
        $fill_the_blank = QuestionType::find(2);

        $questions = Question::factory()->count(4)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);
        $question = Question::factory()->for($exam)->for($fill_the_blank)->create([
            'score' => 20,
        ]);
        $state = $question->states()->create([
            'integer_answer' => null
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(self::STATE_DELETE_ROUTE, [$exam, $question, $state]));

        $second_response->assertStatus(403);
    }

}
