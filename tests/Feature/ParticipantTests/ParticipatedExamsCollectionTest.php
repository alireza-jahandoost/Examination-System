<?php

namespace Tests\Feature\ParticipantTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Laravel\Sanctum\Sanctum;

use App\Models\User;
use App\Models\Exam;
use App\Models\Participant;

use Carbon\Carbon;

class ParticipatedExamsCollectionTest extends TestCase
{
    use RefreshDatabase;

    public const PARTICIPATED_EXAMS_ROUTE = 'participants.participated_exams';

    /**
     * @test
     */
    public function exam_name_must_exists()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'exam_name' => $exam->name,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function exam_id_must_exists()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'exam_id' => $exam->id,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function confirmed_must_exists_if_confirmation_is_required()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => true,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create([
            'is_accepted' => true,
        ]);

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'confirmed' => $participant->is_accepted,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function confirmed_must_be_true_if_confirmation_is_not_required()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create([
            'is_accepted' => false,
        ]);

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'confirmed' => true,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function start_of_exam_must_exists()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'start_of_exam' => Carbon::make($exam->start)->format('Y-m-d H:i:s'),
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function end_of_exam_must_exists()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'end_of_exam' => Carbon::make($exam->end)->format('Y-m-d H:i:s'),
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function total_score_must_exists()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'total_score' => $exam->total_score,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function owner_id_must_exists()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'owner_id' => $owner->id,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function owner_name_must_exists()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'owner_name' => $owner->name,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function grade_must_exists()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'grade' => 0,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function status_must_exists()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'status' => 0,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function result_must_be_paginated()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertJsonStructure([
            'data',
            'meta'
        ]);
    }
}
