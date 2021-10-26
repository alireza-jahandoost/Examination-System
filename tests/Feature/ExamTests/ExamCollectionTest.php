<?php

namespace Tests\Feature\ExamTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Laravel\Sanctum\Sanctum;

use Carbon\Carbon;

use App\Models\User;
use App\Models\Exam;

class ExamCollectionTest extends TestCase
{
    use RefreshDatabase;

    public const INDEX_EXAM_ROUTE = 'exams.index';
    public const INDEX_OWN_EXAM_ROUTE = 'exams.own.index';

    /**
    * @test
    */
    public function with_exam_index_request_if_user_is_authenticated_the_owner_name_of_the_exam_must_be_specified()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );
        $ownerOfExam = User::factory()->create();

        Exam::factory()->state([
            'published' => true
            ])->count(30)->for($ownerOfExam)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'owner_name' => $ownerOfExam->name,
                    ]
                ]
            ],
        ]);
    }

    /**
    * @test
    */
    public function with_exam_index_request_if_user_is_not_authenticated_the_owner_name_of_the_exam_must_be_specified()
    {
        $user = User::factory()->create();

        Exam::factory()->state([
            'published' => true
            ])->count(30)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'owner_name' => $user->name,
                    ]
                ]
            ],
        ]);
    }

    /**
    * @test
    */
    public function with_owned_exams_request_if_user_is_authenticated_the_owner_name_of_the_exam_must_be_specified()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Exam::factory()->state([
            'published' => true
            ])->count(30)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_OWN_EXAM_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'owner_name' => $user->name,
                    ]
                ]
            ],
        ]);
    }

    /**
    * @test
    */
    public function with_owned_exams_request_if_user_is_not_authenticated_user_must_receive_401()
    {
        $user = User::factory()->create();

        Exam::factory()->state([
            'published' => true
            ])->count(30)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function if_exam_belongs_to_user_and_its_published_user_must_see_published_key_equal_to_true_in_owned_exams()
    {
        Sanctum::actingAs(
            $ownerOfExam = User::factory()->create(),
        );

        $exam = Exam::factory()->state([
            'published' => true,
            ])->for($ownerOfExam)->count(30)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'published' => true,
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_exam_belongs_to_user_and_it_is_not_published_user_must_see_published_key_equal_to_false_in_owned_exams()
    {
        Sanctum::actingAs(
            $ownerOfExam = User::factory()->create(),
        );

        $exam = Exam::factory()->state([
            'published' => false,
            ])->for($ownerOfExam)->count(30)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exams' => [
                        [
                            'published' => false,
                        ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_exam_belongs_to_user_and_its_published_user_must_see_published_key_equal_to_true_in_index_exams()
    {
        Sanctum::actingAs(
            $ownerOfExam = User::factory()->create(),
        );

        $exam = Exam::factory()->state([
            'published' => true,
            ])->for($ownerOfExam)->count(30)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'published' => true,
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_exam_does_not_belong_to_user_and_its_published_user_must_not_see_published_key_equal_to_true_in_index_exams()
    {
        $ownerOfExam = User::factory()->create();
        Sanctum::actingAs(
            $currentUser = User::factory()->create(),
        );

        $exam = Exam::factory()->state([
            'published' => true,
            ])->for($ownerOfExam)->count(30)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);
        $response->assertJsonMissing([
            'data' => [
                'exams' => [
                    [
                        'published' => true,
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_exam_does_not_belong_to_user_and_it_is_not_published_user_must_not_see_published_key_equal_to_false_in_index_exams()
    {
        $ownerOfExam = User::factory()->create();
        Sanctum::actingAs(
            $currentUser = User::factory()->create(),
        );

        $exam = Exam::factory()->state([
            'published' => false,
            ])->for($ownerOfExam)->count(30)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);
        $response->assertJsonMissing([
            'data' => [
                'exams' => [
                        [
                            'published' => false,
                        ]
                ]
            ]
        ]);
    }
}
