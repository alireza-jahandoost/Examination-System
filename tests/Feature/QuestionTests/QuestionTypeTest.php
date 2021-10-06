<?php

namespace Tests\Feature\QuestionTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Laravel\Sanctum\Sanctum;

use App\Models\User;

use Database\Seeders\QuestionTypeSeeder;

class QuestionTypeTest extends TestCase
{
    use RefreshDatabase;

    public const QUESTION_TYPE_INDEX = 'question_types.index';
    public const QUESTION_TYPE_SHOW = 'question_types.show';

    /**
     * @test
    */
    public function user_can_see_types_of_questions()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_TYPE_INDEX));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'types' => [
                    [
                        'type_id',
                        'type_name',
                        'type_slug',
                        'number_of_states',
                        'number_of_answers',
                        'type_of_answer'
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
    */
    public function a_guest_user_can_see_types_of_questions()
    {
        $this->seed(QuestionTypeSeeder::class);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_TYPE_INDEX));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'types' => [
                    [
                        'type_id',
                        'type_name',
                        'type_slug',
                        'number_of_states',
                        'number_of_answers',
                        'type_of_answer'
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
    */
    public function user_can_get_the_information_of_a_question_type_by_its_slug()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $this->seed(QuestionTypeSeeder::class);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_TYPE_SHOW, 'true-or-false'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'type' => [
                    'type_id',
                    'type_name',
                    'type_slug',
                    'number_of_states',
                    'number_of_answers',
                    'type_of_answer'
                ]
            ]
        ]);
    }

    /**
     * @test
    */
    public function a_guest_user_can_see_type_of_a_question_by_its_slug()
    {
        $this->seed(QuestionTypeSeeder::class);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::QUESTION_TYPE_SHOW, 'true-or-false'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'type' => [
                    'type_id',
                    'type_name',
                    'type_slug',
                    'number_of_states',
                    'number_of_answers',
                    'type_of_answer'
                ]
            ]
        ]);
    }
}
