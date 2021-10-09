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
    // TODO: owner_name and something like that must exist in examcollection
}
