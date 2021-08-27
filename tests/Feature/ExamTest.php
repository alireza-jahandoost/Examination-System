<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Laravel\Sanctum\Sanctum;

use Carbon\Carbon;

use App\Models\User;
use App\Models\Exam;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    const CREATE_EXAM_ROUTE = 'exams.store';
    const UPDATE_EXAM_ROUTE = 'exams.update';
    const INDEX_EXAM_ROUTE = 'exams.index';
    const SHOW_EXAM_ROUTE = 'exams.show';
    const DELETE_EXAM_ROUTE = 'exams.destroy';

    /**
    * @test
    */
    public function authenticated_user_can_create_an_exam()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::CREATE_EXAM_ROUTE), [
                'exam_name' => 'test',
                'needs_confirmation' => false,
                'password' => null,
                'start_of_exam' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
                'end_of_exam' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                'total_score' => 100,
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('exams', 1);
    }

    /**
    * @test
    */
    public function after_creating_exam_its_info_must_be_returned()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::CREATE_EXAM_ROUTE), [
                'exam_name' => 'test',
                'needs_confirmation' => false,
                'password' => null,
                'start_of_exam' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
                'end_of_exam' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                'total_score' => 100,
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('exams', 1);
        $response->assertJsonStructure([
            'data' => [
                'exam' => [
                    'exam_id',
                    'exam_name',
                    'needs_confirmation',
                    'start_of_exam',
                    'end_of_exam',
                    'creation_time',
                    'last_update'
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function the_password_of_exam_must_be_hashed()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::CREATE_EXAM_ROUTE), [
                'exam_name' => 'test',
                'needs_confirmation' => false,
                'password' => 'password',
                'start_of_exam' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
                'end_of_exam' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                'total_score' => 100,
            ]);
        $response->assertStatus(201);
        $exam = Exam::first();
        $this->assertTrue(Hash::check('password', $exam->password));
    }

    /**
    * @test
    */
    public function fields_password_and_confirmation_required_are_not_required()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::CREATE_EXAM_ROUTE), [
                'exam_name' => 'test',
                'start_of_exam' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
                'end_of_exam' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                'total_score' => 100,
            ]);
        $response->assertStatus(201);
    }

    /**
    * @test
    */
    public function a_guest_user_can_not_make_an_exam()
    {

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::CREATE_EXAM_ROUTE), [
                'exam_name' => 'test',
                'start_of_exam' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
                'end_of_exam' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                'total_score' => 100,
            ]);
        $response->assertStatus(401);
        $this->assertDatabaseCount('exams', 0);
    }

    /**
    * @test
    */
    public function format_of_dates_must_be_YYYY_MM_DD_hh_mm_ss()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::CREATE_EXAM_ROUTE), [
                'exam_name' => 'test',
                'start_of_exam' => Carbon::now()->addMonth()->addHour()->format('Y-m-d'),
                'end_of_exam' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d'),
                'total_score' => 100,
            ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('exams', 0);
    }

    /**
    * @test
    */
    public function total_score_must_be_numeric()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::CREATE_EXAM_ROUTE), [
                'exam_name' => 'test',
                'start_of_exam' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
                'end_of_exam' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                'total_score' => 'aaa',
            ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('exams', 0);
    }

    /**
    * @test
    */
    public function exam_name_is_required()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::CREATE_EXAM_ROUTE), [
                'start_of_exam' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
                'end_of_exam' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                'total_score' => 100,
            ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('exams', 0);
    }

    /**
    * @test
    */
    public function start_and_end_of_exam_are_required()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::CREATE_EXAM_ROUTE), [
                'exam_name' => 'test',
                'total_score' => 100,
            ]);
        $response->assertStatus(422);
        $this->assertDatabaseCount('exams', 0);
    }

    /**
    * @test
    */
    public function user_can_update_his_exams_name()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'exam_name' => 'test2',
            ]);
        $exam->refresh();
        $response->assertStatus(200);
        $this->assertTrue($exam->name === 'test2');
    }

    /**
    * @test
    */
    public function user_can_update_his_exams_needs_confirmation()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
            'confirmation_required' => false
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'needs_confirmation' => true
            ]);
        $exam->refresh();
        $response->assertStatus(200);
        $this->assertTrue($exam->confirmation_required === true);
    }

    /**
    * @test
    */
    public function user_can_update_his_exams_password()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
            'password' => 'password',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'password' => 'new password',
            ]);
        $exam->refresh();
        $response->assertStatus(200);
        $this->assertTrue(Hash::check('new password', $exam->password));
    }

    /**
    * @test
    */
    public function user_can_update_his_exams_start()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);

        $start = $exam->start;

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'start_of_exam' => Carbon::now()->addMonths(2)->addHour()->format('Y-m-d H:i:s'),
            ]);
        $exam->refresh();
        $response->assertStatus(200);
        $this->assertTrue($exam->start !== $start);
    }

    /**
    * @test
    */
    public function user_can_update_his_exams_end()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);

        $end = $exam->end;

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'end_of_exam' => Carbon::now()->addMonths(2)->addHours(3)->format('Y-m-d H:i:s'),
            ]);
        $exam->refresh();
        $response->assertStatus(200);
        $this->assertTrue($exam->end !== $end);
    }

    /**
    * @test
    */
    public function user_can_update_his_exams_total_score()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'total_score' => 20,
            ]);
        $exam->refresh();
        $response->assertStatus(200);
        $this->assertTrue($exam->total_score === 20);
    }

    /**
    * @test
    */
    public function user_can_update_name_if_its_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'exam_name' => Str::repeat('a', 300)
            ]);
        $exam->refresh();
        $response->assertStatus(422);
        $this->assertTrue($exam->name === 'test');
    }

    /**
    * @test
    */
    public function user_can_update_password_if_its_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);
        $exam->password = 'password';
        $exam->save();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'password' => Str::repeat('a', 300)
            ]);
        $exam->refresh();
        $response->assertStatus(422);
        $this->assertTrue(Hash::check('password', $exam->password));
    }

    /**
    * @test
    */
    public function user_can_update_start_if_its_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);

        $start = $exam->start;

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'start_of_exam' => Carbon::now()->addMonths(2)->addHour()->format('Y-m-d'),
            ]);
        $exam->refresh();
        $response->assertStatus(422);
        $this->assertTrue($exam->start === $start);
    }

    /**
    * @test
    */
    public function user_can_update_end_if_its_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);

        $end = $exam->end;

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'end_of_exam' => Carbon::now()->addMonths(2)->addHours(3)->format('Y-m-d'),
            ]);
        $exam->refresh();
        $response->assertStatus(422);
        $this->assertTrue($exam->end === $end);
    }

    /**
    * @test
    */
    public function user_can_update_total_score_if_its_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'total_score' => 'aaa',
            ]);
        $exam->refresh();
        $response->assertStatus(422);
        $this->assertTrue($exam->total_score === 100);
    }

    /**
    * @test
    */
    public function user_can_update_confirmation_required_if_its_valid()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
            'confirmation_required' => false
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'needs_confirmation' => 'a'
            ]);
        $exam->refresh();
        $response->assertStatus(422);
        $this->assertTrue($exam->confirmation_required === false);
    }

    /**
    * @test
    */
    public function user_just_can_update_his_exams()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $anotherUser = User::factory()->create();

        $exam = $anotherUser->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'total_score' => 20,
            ]);
        $exam->refresh();
        $response->assertStatus(403);
        $this->assertTrue($exam->total_score === 100);
    }

    /**
    * @test
    */
    public function user_will_receive_new_information_after_updating_exam()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = $user->ownedExams()->create([
            'name' => 'test',
            'start' => Carbon::now()->addMonth()->addHour()->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'total_score' => 100,
            'password' => 'password',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->put(route(self::UPDATE_EXAM_ROUTE, $exam->id), [
                'total_score' => 20,
                'exam_name' => 'new name',
            ]);
        $exam->refresh();
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exam' => [
                    'total_score' => 20,
                    'exam_name' => 'new name',
                ]
            ]
        ]);
        $response->assertJsonStructure([
            'data' => [
                'exam' => [
                    'exam_id',
                    'exam_name',
                    'needs_confirmation',
                    'start_of_exam',
                    'end_of_exam',
                    'total_score',
                    'creation_time',
                    'last_update'
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_receive_exams_paginated()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Exam::factory()->count(30)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertJsonStructure([
            'data' => [
                [
                    'exam' => [
                        'exam_id',
                        'exam_name',
                        'needs_confirmation',
                        'start_of_exam',
                        'end_of_exam',
                        'total_score',
                        'creation_time',
                        'last_update',
                    ]
                ]
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_dont_need_to_be_authenticated_to_see_exams()
    {
        $user = User::factory()->create();

        Exam::factory()->count(30)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                [
                    'exam' => [
                        'exam_id',
                        'exam_name',
                        'needs_confirmation',
                        'start_of_exam',
                        'end_of_exam',
                        'total_score',
                        'creation_time',
                        'last_update',
                    ]
                ]
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_receive_an_exam_information()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'exam' => [
                    'exam_id',
                    'exam_name',
                    'needs_confirmation',
                    'start_of_exam',
                    'end_of_exam',
                    'total_score',
                    'creation_time',
                    'last_update',
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function guest_users_can_show_an_exam()
    {

        $user = User::factory()->create();

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'exam' => [
                    'exam_id',
                    'exam_name',
                    'needs_confirmation',
                    'start_of_exam',
                    'end_of_exam',
                    'total_score',
                    'creation_time',
                    'last_update',
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_delete_his_exam()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([])->delete(route(self::DELETE_EXAM_ROUTE, $exam->id));

        $response->assertStatus(202);
        $this->assertDeleted($exam);
    }

    /**
    * @test
    */
    public function user_can_not_delete_others_exams()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $anotherUser = User::factory()->create();

        $exam = Exam::factory()->for($anotherUser)->create();

        $response = $this->withHeaders([])->delete(route(self::DELETE_EXAM_ROUTE, $exam->id));

        $response->assertStatus(403);
        $this->assertDatabaseCount('exams', 1);
    }
}
