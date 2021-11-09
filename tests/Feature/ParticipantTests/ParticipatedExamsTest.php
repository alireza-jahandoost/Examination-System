<?php

namespace Tests\Feature\ParticipantTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Laravel\Sanctum\Sanctum;

use App\Models\User;
use App\Models\Exam;
use App\Models\Participant;

class ParticipatedExamsTest extends TestCase
{
    use RefreshDatabase;

    public const PARTICIPATED_EXAMS_ROUTE = 'participants.participated_exams';

    /**
     * @test
     */
    public function user_can_get_his_participated_exams()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $exam2 = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant2 = Participant::factory()->for($user)->for($exam2)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertStatus(200);
        $this->assertEquals(count($response->json()['data']['exams']), 2);
    }

    /**
     * @test
     */
    public function guest_user_can_not_get_participated_exams()
    {
        $user = User::factory()->create();

        $owner = User::factory()->create();

        $exam = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant = Participant::factory()->for($user)->for($exam)->create();

        $exam2 = Exam::factory()->for($owner)->create([
            'confirmation_required' => false,
        ]);

        $participant2 = Participant::factory()->for($user)->for($exam2)->create();

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertStatus(401);
    }
}
