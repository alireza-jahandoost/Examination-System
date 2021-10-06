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

use Database\Seeders\QuestionTypeSeeder;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    public const QUESTION_STORE_ROUTE = 'questions.store';
    public const QUESTION_INDEX_ROUTE = 'questions.index';
    public const QUESTION_UPDATE_ROUTE = 'questions.update';
    public const QUESTION_SHOW_ROUTE = 'questions.show';
    public const QUESTION_DELETE_ROUTE = 'questions.destroy';

    /**
     * @test
     */
    public function an_authenticated_user_can_create_a_question_for_his_exam()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => 'test',
             'question_type_id' => 1,
             'question_score' => 20,
         ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('questions', 1);
    }

    /**
     * @test
     */
    public function user_will_receive_the_question_information_after_creating_a_question()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => 'test',
             'question_type_id' => 1,
             'question_score' => 20,
         ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('questions', 1);
        $response->assertJsonStructure([
             'data' => [
                 'question' => [
                     'question_text',
                     'question_score',
                     'can_be_shuffled',
                     'question_type' => [
                         'question_type_name',
                         'question_type_link'
                     ]
                 ]
             ]
         ]);
    }

    /**
     * @test
     */
    public function a_guest_user_can_not_create_a_question_for_any_exam()
    {
        $user = User::factory()->create();

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => 'test',
             'question_type_id' => 1,
             'question_score' => 20,
         ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
     * @test
     */
    public function an_authenticated_user_can_not_create_a_question_for_another_exam()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $anotherUser = User::factory()->create();

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($anotherUser)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => 'test',
             'question_type_id' => 1,
             'question_score' => 20,
         ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
     * @test
     */
    public function for_creating_a_question_question_text_is_required()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_type_id' => 1,
             'question_score' => 20,
         ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
     * @test
     */
    public function for_creating_a_question_score_is_required()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_type_id' => 1,
             'question_text' => 'test',
         ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
     * @test
     */
    public function for_creating_a_question_question_type_id_is_required()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => 'test',
             'question_score' => 20,
         ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
     * @test
     */
    public function for_creating_a_question_can_be_shuffled_is_nullable()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => 'test',
             'question_type_id' => 1,
             'question_score' => 20,
         ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('questions', 1);
    }

    /**
     * @test
     */
    public function for_creating_a_question_score_must_be_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => 'test',
             'question_type_id' => 1,
             'question_score' => 'aaa',
         ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
     * @test
     */
    public function for_creating_a_question_question_type_id_must_be_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => 'test',
             'question_type_id' => 100,
             'question_score' => 20,
         ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
     * @test
     */
    public function for_creating_a_question_question_text_must_be_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => Str::repeat('a', 10001),
             'question_type_id' => 1,
             'question_score' => 20,
         ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
     * @test
     */
    public function for_creating_a_question_can_be_shuffled_must_be_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->post(route(self::QUESTION_STORE_ROUTE, $exam->id), [
             'question_text' => 'test',
             'question_type_id' => 1,
             'question_score' => 20,
             'can_be_shuffled' => 'aaa'
         ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
     * @test
     */
    public function user_can_update_all_the_fields_of_questions_of_his_exams_except_question_type()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create([
             'can_be_shuffled' => false
         ]);

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->put(route(self::QUESTION_UPDATE_ROUTE, [$exam->id, $question->id]), [
             'question_text' => 'test',
             'question_score' => 20,
             'can_be_shuffled' => true
         ]);

        $question->refresh();
        $response->assertStatus(200);
        $this->assertTrue($question->question_text === 'test');
        $this->assertTrue($question->score === 20);
        $this->assertTrue($question->can_be_shuffled === true);
    }

    /**
     * @test
     */
    public function for_updating_question_text_is_nullable()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create([
             'can_be_shuffled' => false
         ]);

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->put(route(self::QUESTION_UPDATE_ROUTE, [$exam->id, $question->id]), [
             'question_score' => 20,
             'can_be_shuffled' => true
         ]);

        $question->refresh();
        $response->assertStatus(200);
        $this->assertTrue($question->score === 20);
        $this->assertTrue($question->can_be_shuffled === true);
    }

    /**
     * @test
     */
    public function question_type_id_can_not_be_modified()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create([
             'can_be_shuffled' => false
         ]);

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->put(route(self::QUESTION_UPDATE_ROUTE, [$exam->id, $question->id]), [
                'question_type_id' => 2,
         ]);

        $question->refresh();
        $response->assertStatus(200);
        $this->assertTrue($question->question_type_id === 3);
    }

    /**
     * @test
     */
    public function for_updating_can_be_shuffled_is_nullable()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create([
             'can_be_shuffled' => false
         ]);

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->put(route(self::QUESTION_UPDATE_ROUTE, [$exam->id, $question->id]), [
             'question_text' => 'test',
             'question_score' => 20,
         ]);

        $question->refresh();
        $response->assertStatus(200);
        $this->assertTrue($question->question_text === 'test');
        $this->assertTrue($question->score === 20);
    }

    /**
     * @test
     */
    public function for_updating_question_score_is_nullable()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create([
             'can_be_shuffled' => false
         ]);

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->put(route(self::QUESTION_UPDATE_ROUTE, [$exam->id, $question->id]), [
             'question_text' => 'test',
             'can_be_shuffled' => true
         ]);

        $question->refresh();
        $response->assertStatus(200);
        $this->assertTrue($question->question_text === 'test');
        $this->assertTrue($question->can_be_shuffled === true);
    }

    /**
     * @test
     */
    public function for_updatings_question_must_belong_to_that_specified_exam()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $anotherExam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create([
             'can_be_shuffled' => false
         ]);

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->put(route(self::QUESTION_UPDATE_ROUTE, [$anotherExam->id, $question->id]), [
             'question_text' => 'test',
             'can_be_shuffled' => true
         ]);

        $question->refresh();
        $response->assertStatus(403);
        $this->assertFalse($question->question_text === 'test');
        $this->assertFalse($question->question_type_id === 1);
        $this->assertFalse($question->can_be_shuffled === true);
    }

    /**
     * @test
     */
    public function user_will_receive_the_question_information_after_updating()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create([
             'can_be_shuffled' => false
         ]);

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->put(route(self::QUESTION_UPDATE_ROUTE, [$exam->id, $question->id]), [
             'question_text' => 'test',
             'can_be_shuffled' => true
         ]);

        $question->refresh();
        $response->assertStatus(200);
        $this->assertTrue($question->question_text === 'test');
        $this->assertTrue($question->can_be_shuffled === true);
        $response->assertJsonStructure([
             'data' => [
                 'question' => [
                     'question_text',
                     'question_score',
                     'can_be_shuffled',
                     'question_type' => [
                         'question_type_name',
                         'question_type_link'
                     ]
                 ]
             ]
         ]);
    }

    /**
     * @test
     */
    public function a_guest_user_can_not_update_any_exam()
    {
        $this->seed(QuestionTypeSeeder::class);

        $user = User::factory()->create();

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create([
             'can_be_shuffled' => false
         ]);

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->put(route(self::QUESTION_UPDATE_ROUTE, [$exam->id, $question->id]), [
             'question_text' => 'test',
             'can_be_shuffled' => true
         ]);

        $question->refresh();
        $response->assertStatus(401);
        $this->assertFalse($question->question_text === 'test');
        $this->assertFalse($question->question_type_id === 1);
        $this->assertFalse($question->can_be_shuffled === true);
    }

    /**
     * @test
     */
    public function a_user_can_not_update_the_exam_of_another_user()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $anotherUser = User::factory()->create();

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($anotherUser)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create([
             'can_be_shuffled' => false
         ]);

        $response = $this->withHeaders([
             'Accept' => 'application/json'
             ])->put(route(self::QUESTION_UPDATE_ROUTE, [$exam->id, $question->id]), [
             'question_text' => 'test',
             'can_be_shuffled' => true
         ]);

        $question->refresh();
        $response->assertStatus(403);
        $this->assertFalse($question->question_text === 'test');
        $this->assertFalse($question->question_type_id === 1);
        $this->assertFalse($question->can_be_shuffled === true);
    }

    /**
    * @test
    */
    public function user_can_get_all_the_questions_of_his_exam()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->count(30)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::QUESTION_INDEX_ROUTE, [$exam->id]));
        $response->assertStatus(200);
        $response->assertJsonStructure([
             'data' => [
                 'questions' => [
                     [
                         'question_id',
                         'question_link',
                     ],
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
    public function another_user_can_not_get_the_questions_of_other_exams()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $anotherUser = User::factory()->create();

        $exam = Exam::factory()->for($anotherUser)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->count(30)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::QUESTION_INDEX_ROUTE, [$exam->id]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function guest_user_can_not_see_questions_of_exams()
    {
        $user = User::factory()->create();

        $this->seed(QuestionTypeSeeder::class);

        $anotherUser = User::factory()->create();

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->count(30)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::QUESTION_INDEX_ROUTE, [$exam->id]));
        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function user_can_get_a_single_question_of_his_exams()
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
                     'question_text',
                     'question_score',
                     'can_be_shuffled',
                     'question_type' => [
                         'question_type_name',
                         'question_type_link'
                     ]
                 ]
             ]
         ]);
    }

    /**
    * @test
    */
    public function guest_user_can_not_see_any_question()
    {
        $user = User::factory()->create();

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::QUESTION_SHOW_ROUTE, [$exam->id, $question->id]));
        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function user_can_not_get_information_of_a_question_that_do_not_belong_to_him()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);
        $anotherUser = User::factory()->create();

        $exam = Exam::factory()->for($anotherUser)->create();
        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::QUESTION_SHOW_ROUTE, [$exam->id, $question->id]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function user_can_not_request_to_see_a_question_that_do_not_belong_to_that_exam_that_he_specified()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $anotherExam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::QUESTION_SHOW_ROUTE, [$anotherExam->id, $question->id]));
        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function user_can_delete_his_questions()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create();

        $this->assertDatabaseCount('questions', 1);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->delete(route(self::QUESTION_DELETE_ROUTE, [$exam->id, $question->id]));
        $response->assertStatus(202);
        $this->assertDatabaseCount('questions', 0);
    }

    /**
    * @test
    */
    public function guest_user_can_not_delete_any_question()
    {
        $user = User::factory()->create();

        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create();

        $this->assertDatabaseCount('questions', 1);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->delete(route(self::QUESTION_DELETE_ROUTE, [$exam->id, $question->id]));
        $response->assertStatus(401);
        $this->assertDatabaseCount('questions', 1);
    }

    /**
    * @test
    */
    public function user_can_not_delete_other_users_question()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $anotherUser = User::factory()->create();
        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($anotherUser)->create();

        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create();

        $this->assertDatabaseCount('questions', 1);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->delete(route(self::QUESTION_DELETE_ROUTE, [$exam->id, $question->id]));
        $response->assertStatus(403);
        $this->assertDatabaseCount('questions', 1);
    }

    /**
    * @test
    */
    public function user_can_not_delete_a_question_that_do_not_belong_to_specified_exam()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $this->seed(QuestionTypeSeeder::class);

        $exam = Exam::factory()->for($user)->create();
        $anotherExam = Exam::factory()->for($user)->create();

        $question_type = QuestionType::find(3);

        $question = Question::factory()->for($exam)->for($question_type)->create();

        $this->assertDatabaseCount('questions', 1);
        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->delete(route(self::QUESTION_DELETE_ROUTE, [$anotherExam->id, $question->id]));
        $response->assertStatus(403);
        $this->assertDatabaseCount('questions', 1);
    }
}
