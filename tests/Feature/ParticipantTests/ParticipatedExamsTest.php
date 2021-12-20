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
    public function participated_exams_must_be_sorted_by_created_at_of_participant_model_desc()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
        );

        $owner = User::factory()->create();

        $exams = Exam::factory()->count(10)->for($owner)->state([
            'published' => true
        ])->create();
        $exams->each(function ($exam, $idx) use ($user) {
            $participant = Participant::factory()->for($exam)->for($user)->create();
            $participant->created_at = Carbon::now()->subDays(rand(1, 25))->subHours(rand(1, 24))->format('Y-m-d H:i:s');
            $participant->save();
        });

        $response = $this->withHeaders([
            'accept' => 'application/json',
        ])->get(route(self::PARTICIPATED_EXAMS_ROUTE));

        $response->assertStatus(200);

        $exams = $response->json()['data']['exams'];

        for ($i = 1;$i < count($exams);$i ++) {
            $currentParticipant = Participant::where('exam_id', $exams[$i]['exam_id'])->first();
            $prevParticipant = Participant::where('exam_id', $exams[$i-1]['exam_id'])->first();
            $this->assertTrue($currentParticipant->created_at->lessThanOrEqualTo($prevParticipant->created_at));
        }
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
