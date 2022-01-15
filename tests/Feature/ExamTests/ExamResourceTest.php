<?php

namespace Tests\Feature\ExamTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Laravel\Sanctum\Sanctum;

use Carbon\Carbon;

use App\Models\User;
use App\Models\Exam;
use App\Models\Participant;

class ExamResourceTest extends TestCase
{
    use RefreshDatabase;

    public const SHOW_EXAM_ROUTE = 'exams.show';

    /**
    * @test
    */
    public function if_user_is_authenticated_user_must_see_owner_name_in_show_method_of_exams()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $ownerOfExam = User::factory()->create();

        $exam = Exam::factory()->state([
            'published' => true,
            ])->for($ownerOfExam)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exam' => [
                    'owner_name' => $ownerOfExam->name,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_user_is_authenticated_user_must_see_has_password_and_has_password_must_be_true_if_exam_has_password_in_show_method_of_exams()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $ownerOfExam = User::factory()->create();

        $exam = Exam::factory()->state([
            'published' => true,
            'password' => bcrypt('password'),
            ])->for($ownerOfExam)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exam' => [
                    'has_password' => true,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_user_is_authenticated_user_must_see_has_password_and_has_password_must_be_false_if_exam_doesnt_have_password_in_show_method_of_exams()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $ownerOfExam = User::factory()->create();

        $exam = Exam::factory()->state([
            'published' => true,
            ])->for($ownerOfExam)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exam' => [
                    'has_password' => false,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_user_is_authenticated_and_user_registered_in_an_exam_user_must_see_is_registered_field_equal_to_true()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );
        $ownerOfExam = User::factory()->create();

        $exam = Exam::factory()->state([
            'published' => true,
            'password' => bcrypt('password'),
            ])->for($ownerOfExam)->create();

        $participant = Participant::factory()->for($exam)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exam' => [
                    'is_registered' => true,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_user_is_authenticated_and_user_was_not_register_in_an_exam_user_must_see_is_registered_field_equal_to_false()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );
        $ownerOfExam = User::factory()->create();

        $exam = Exam::factory()->state([
            'published' => true,
            'password' => bcrypt('password'),
            ])->for($ownerOfExam)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exam' => [
                    'is_registered' => false,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_exam_belongs_to_user_and_its_published_user_must_see_published_key_equal_to_true()
    {
        Sanctum::actingAs(
            $ownerOfExam = User::factory()->create(),
        );

        $exam = Exam::factory()->state([
            'published' => true,
            ])->for($ownerOfExam)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exam' => [
                    'published' => true,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_exam_belongs_to_user_and_it_is_not_published_user_must_see_published_key_equal_to_false()
    {
        Sanctum::actingAs(
            $ownerOfExam = User::factory()->create(),
        );

        $exam = Exam::factory()->state([
            'published' => false,
            ])->for($ownerOfExam)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exam' => [
                    'published' => false,
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function authenticated_user_can_not_see_published_key_of_another_users_exam()
    {
        $ownerOfExam = User::factory()->create();
        Sanctum::actingAs(
            $currentUser = User::factory()->create(),
        );

        $exam = Exam::factory()->state([
            'published' => true,
            ])->for($ownerOfExam)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJsonMissing([
            'data' => [
                'exam' => [
                    'published' => true,
                ]
            ]
        ]);
    }
}
