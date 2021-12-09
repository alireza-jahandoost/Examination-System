<?php

namespace Tests\Feature\ExamTests;

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

use Carbon\Carbon;

class PublishFeatureTest extends TestCase
{
    use RefreshDatabase;

    public const PUBLISH_EXAM_ROUTE = 'exams.publish';
    public const UNPUBLISH_EXAM_ROUTE = 'exams.unpublish';
    public const UPDATE_EXAM_ROUTE = 'exams.update';
    public const QUESTION_UPDATE_ROUTE = 'questions.update';
    public const QUESTION_STORE_ROUTE = 'questions.store';
    public const STATE_CREATE_ROUTE = 'states.store';
    public const STATE_UPDATE_ROUTE = 'states.update';
    public const STATE_DELETE_ROUTE = 'states.destroy';
    public const QUESTION_DELETE_ROUTE = 'questions.destroy';
    public const SHOW_EXAM_ROUTE = 'exams.show';
    public const INDEX_EXAM_ROUTE = 'exams.index';
    public const INDEX_OWN_EXAM_ROUTE = 'exams.own.index';

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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));
        $response->assertStatus(401);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function just_user_itPublishFeatureTest_can_publish_his_exam()
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => null,
            'text_answer' => 'test'
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => null,
            'text_answer' => 'test'
        ]);

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function fill_the_blank_questions_must_have_3_open_braces_and_3_close_braces_as_input_place_in_their_text()
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
            'score' => 20,
            'question_text' => 'test test test',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => null,
            'text_answer' => 'test'
        ]);
        $question->states()->create([
            'integer_answer' => null,
            'text_answer' => 'test'
        ]);

        $failed_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $failed_response->assertStatus(422);
        $failed_response->assertJsonStructure([
            'message'
        ]);
        $this->assertDatabaseHas('exams', [
            'published' => false
        ]);

        $question->question_text = 'test {{{}}} test test {{{}}} test test test';
        $question->save();

        $failed_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $failed_response->assertStatus(422);
        $failed_response->assertJsonStructure([
            'message'
        ]);
        $this->assertDatabaseHas('exams', [
            'published' => false
        ]);

        $question->question_text = 'test test test {{{}}} test test test';
        $question->save();

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => null,
            'text_answer' => 'test'
        ]);

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(202);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(202);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 0,
            'text_answer' => 'test'
        ]);

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(202);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->first()->delete();

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(202);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 2,
            'text_answer' => 'test'
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $second_response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
        $question->states()->create([
            'integer_answer' => 3,
            'text_answer' => 'test'
        ]);

        $third_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $third_response->assertStatus(202);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);

        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function select_questions_have_to_have_one_answer()
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);

        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function select_questions_can_not_have_more_than_1_answer()
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);

        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);

        $must_be_deleted_state = $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);

        $must_be_deleted_state->delete();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);

        $question->states()->create([
            'integer_answer' => 1,
            'text_answer' => 'test'
        ]);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::UPDATE_EXAM_ROUTE, [$exam]), [
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::QUESTION_UPDATE_ROUTE, [$exam, $questions[0]]), [
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(PublishFeatureTest::QUESTION_STORE_ROUTE, [$exam]), [
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(PublishFeatureTest::QUESTION_DELETE_ROUTE, [$exam, $questions[0]]));

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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(PublishFeatureTest::STATE_CREATE_ROUTE, [$exam, $question]), [
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
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
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(PublishFeatureTest::STATE_DELETE_ROUTE, [$exam, $question, $state]));

        $second_response->assertStatus(403);
    }

    /**
    * @test
    */
    public function use_can_unpublish_his_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);
        $this->assertDatabaseHas('exams', [
            'published' => false
        ]);
        $this->assertDatabaseMissing('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_it_will_not_be_indexed_anymore()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);

        $this->assertTrue($response->json()['data']['exams'] === []);

        Sanctum::actingAs(
            $anotherUser = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);

        $this->assertTrue($response->json()['data']['exams'] === []);
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_it_will_be_indexed_in_own_exams()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'exam_id' => $exam->id,
                    ]
                ]
            ]
        ]);

        Sanctum::actingAs(
            $anotherUser = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);

        $this->assertTrue($response->json()['data']['exams'] === []);
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_just_owner_can_show_the_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::SHOW_EXAM_ROUTE, $exam));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'exam' => [
                    'exam_id' => $exam->id,
                ]
            ]
        ]);

        Sanctum::actingAs(
            $anotherUser = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::SHOW_EXAM_ROUTE, $exam));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function if_user_publish_after_unpublishing_then_it_will_be_shown_for_all_users_again()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::SHOW_EXAM_ROUTE, $exam));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'exam' => [
                    'exam_id' => $exam->id,
                ]
            ]
        ]);

        Sanctum::actingAs(
            $anotherUser = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::SHOW_EXAM_ROUTE, $exam));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'exam' => [
                    'exam_id' => $exam->id,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_user_publish_the_exam_after_unpublishing_it_will_be_indexed_in_index_exams_page()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);

        $this->assertFalse($response->json()['data'] === []);

        Sanctum::actingAs(
            $anotherUser = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);

        $this->assertFalse($response->json()['data'] === []);
    }

    /**
    * @test
    */
    public function if_user_publish_the_exam_after_unpublishing_it_will_still_be_indexed_in_own_exams_page()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'exam_id' => $exam->id,
                    ]
                ]
            ]
        ]);

        Sanctum::actingAs(
            $anotherUser = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get(route(PublishFeatureTest::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);

        $this->assertTrue($response->json()['data']['exams'] === []);
    }

    /**
    * @test
    */
    public function user_can_not_unpublish_the_exam_if_exam_started()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100,
            'start' => Carbon::now()->subMinute()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->subMinute()->addHours(2)->format('Y-m-d H:i:s'),
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('exams', [
            'published' => false
        ]);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function just_owner_can_unpublish_the_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100,
            'start' => Carbon::now()->addMinute()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMinute()->addHours(2)->format('Y-m-d H:i:s'),
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        Sanctum::actingAs(
            $anotherUser = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('exams', [
            'published' => false
        ]);
        $this->assertDatabaseHas('exams', [
            'published' => true
        ]);
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_exam_information_can_be_changed()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100,
            'name' => 'test',
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::UPDATE_EXAM_ROUTE, [$exam]), [
                'exam_name' => 'test2',
            ]);

        $second_response->assertStatus(200);
        $exam->refresh();
        $this->assertTrue($exam->name === 'test2');
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_questions_of_exam_can_be_changed()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::QUESTION_UPDATE_ROUTE, [$exam, $questions[0]]), [
                'question_text' => 'test',
            ]);

        $second_response->assertStatus(200);
        $questions[0]->refresh();
        $this->assertTrue($questions[0]->question_text === 'test');
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_user_can_create_questions()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(PublishFeatureTest::QUESTION_STORE_ROUTE, [$exam]), [
                 'question_text' => 'test',
                 'question_type_id' => 1,
                 'question_score' => 20,
            ]);

        $second_response->assertStatus(201);
        $this->assertDatabaseHas('questions', [
            'question_text' => 'test',
            'question_type_id' => 1,
            'score' => 20,
        ]);
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_user_can_delete_questions()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(PublishFeatureTest::QUESTION_DELETE_ROUTE, [$exam, $questions[0]]));

        $second_response->assertStatus(202);
        $this->assertDatabaseMissing('questions', [
            'id' => $questions[0]->id,
        ]);
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_user_can_create_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
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
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(PublishFeatureTest::STATE_CREATE_ROUTE, [$exam, $question]), [
                'text_part' => 'test test'
            ]);

        $second_response->assertStatus(201);
        $this->assertDatabaseHas('states', [
            'text_answer' => 'test test',
        ]);
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_user_can_update_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
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
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::STATE_UPDATE_ROUTE, [$exam, $question, $state]), [
                'text_part' => 'test test test'
            ]);

        $second_response->assertStatus(200);
        $this->assertDatabaseHas('states', [
            'text_answer' => 'test test test',
        ]);
    }

    /**
    * @test
    */
    public function after_unpublishing_the_exam_user_can_delete_states()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100
        ]);
        $exam->published = true;
        $exam->save();
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
        ])->put(route(PublishFeatureTest::UNPUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(202);

        $second_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(PublishFeatureTest::STATE_DELETE_ROUTE, [$exam, $question, $state]));

        $second_response->assertStatus(202);
        $this->assertDatabaseMissing('states', [
            'id' => $state->id,
        ]);
    }

    /**
    * @test
    */
    public function when_publishing_the_exam_start_of_exam_must_be_in_future()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $start = Carbon::now()->subMinute();
        $end = Carbon::make($start)->addHours(2);
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseHas('exams', [
            'published' => false
        ]);
    }

    /**
    * @test
    */
    public function when_publishing_the_exam_end_of_the_exam_must_be_after_of_start_of_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $start = Carbon::now()->addMinute();
        $end = Carbon::make($start)->subMinute();
        $exam = Exam::factory()->for($user)->create([
            'total_score' => 100,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
        $question_type = QuestionType::find(1);

        $questions = Question::factory()->count(5)->for($exam)->for($question_type)->create([
            'score' => 20
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(PublishFeatureTest::PUBLISH_EXAM_ROUTE, [$exam]));

        $response->assertStatus(422);
        $this->assertDatabaseHas('exams', [
            'published' => false
        ]);
    }
}
