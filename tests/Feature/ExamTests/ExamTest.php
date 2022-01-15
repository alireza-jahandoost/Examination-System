<?php

namespace Tests\Feature\ExamTests;

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

    public const CREATE_EXAM_ROUTE = 'exams.store';
    public const UPDATE_EXAM_ROUTE = 'exams.update';
    public const INDEX_EXAM_ROUTE = 'exams.index';
    public const INDEX_OWN_EXAM_ROUTE = 'exams.own.index';
    public const SHOW_EXAM_ROUTE = 'exams.show';
    public const DELETE_EXAM_ROUTE = 'exams.destroy';
    public const LOGOUT_ROUTE = 'authentication.logout';

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

        Exam::factory()->state([
            'published' => true
            ])->count(30)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertJsonStructure([
            'data' => [
                'exams' => [
                    [
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
    public function exams_must_be_sorted_by_start_time_desc()
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
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $exams = array_map(fn ($current) => Carbon::createFromFormat('Y-m-d H:i:s', $current['start_of_exam']), $response->json()['data']['exams']);

        for ($i = 1;$i < count($exams);$i ++) {
            $this->assertTrue($exams[$i]->lessThanOrEqualTo($exams[$i-1]));
        }
    }

    /**
    * @test
    */
    public function exams_must_be_sorted_by_start_time_desc_in_searching()
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
            ])->get(route(self::INDEX_EXAM_ROUTE, ['search' => 'a']));

        $exams = array_map(fn ($current) => Carbon::createFromFormat('Y-m-d H:i:s', $current['start_of_exam']), $response->json()['data']['exams']);

        for ($i = 1;$i < count($exams);$i ++) {
            $this->assertTrue($exams[$i]->lessThanOrEqualTo($exams[$i-1]));
        }
    }

    /**
    * @test
    */
    public function user_dont_need_to_be_authenticated_to_see_exams()
    {
        $user = User::factory()->create();

        Exam::factory()->state([
            'published' => true
            ])->count(30)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'exams' => [
                    [
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
    public function user_can_not_see_unpublished_exam_of_another_user_in_index_page()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*'],
        );

        $anotherUser = User::factory()->create();
        $exam = Exam::factory()->for($anotherUser)->create();
        Exam::factory()->for($anotherUser)->state([
            'published' => true,
            ])->count(5)->create();


        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::INDEX_EXAM_ROUTE));

        $response->assertStatus(200);
        $response->assertDontSee('"exam_id":1', false);
    }

    /**
    * @test
    */
    public function user_can_receive_an_exam_information_if_authenticated()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exam = Exam::factory()->state([
            'published' => true,
            ])->for($user)->create();

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
    public function guest_users_can_not_see_exams()
    {
        $user = User::factory()->create();

        $exam = Exam::factory()->state([
            'published' => true
            ])->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function user_can_see_his_unpublished_exam()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*'],
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
    public function user_can_see_the_owner_id_and_owner_link_of_exam_in_show_route()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*'],
        );

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'exam' => [
                    'owner_id' => $exam->user_id,
                    'owner_link' => route('users.show', $exam->user),
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_not_see_unpublished_exam_of_another_user_in_show_page()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*'],
        );

        $anotherUser = User::factory()->create();

        $exam = Exam::factory()->for($anotherUser)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(403);
    }

    /**
    * @test
    */
    public function guest_user_can_not_see_any_unpublished_exams()
    {
        $user = User::factory()->create();

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->get(route(self::SHOW_EXAM_ROUTE, $exam->id));

        $response->assertStatus(401);
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

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(self::DELETE_EXAM_ROUTE, $exam->id));

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

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(self::DELETE_EXAM_ROUTE, $exam->id));

        $response->assertStatus(403);
        $this->assertDatabaseCount('exams', 1);
    }

    /**
    * @test
    */
    public function a_guest_user_can_not_delete_any_exam()
    {
        $user = User::factory()->create();

        $exam = Exam::factory()->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->delete(route(self::DELETE_EXAM_ROUTE, $exam->id));

        $response->assertStatus(401);
        $this->assertDatabaseCount('exams', 1);
    }

    /**
    * @test
    */
    public function user_can_index_all_of_his_exams_in_own_exam_index_page_even_if_they_are_not_published()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Exam::factory()->state([
            'published' => true
        ])->for($user)->create();
        Exam::factory()->state([
            'published' => false
        ])->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'exams' => [
                    [
                        'exam_id' => 1
                    ],
                    [
                        'exam_id' => 2
                    ]
                ]
            ]
        ]);
    }

    /**
    * @test
    */
    public function owned_exams_must_be_ordered_by_creation_time_desc()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $exams = Exam::factory(10)->state([
            'published' => true,
        ])->for($user)->create();
        $exams->each(function ($exam, $key) {
            $exam->created_at = Carbon::now()->subDays(rand(1, 25))->subHours(rand(1, 24))->format('Y-m-d H:i:s');
            $exam->save();
        });

        $exams = Exam::factory(10)->state([
            'published' => false,
        ])->for($user)->create();

        $exams->each(function ($exam, $key) {
            $exam->created_at = Carbon::now()->subDays(rand(1, 25))->subHours(rand(1, 24))->format('Y-m-d H:i:s');
            $exam->save();
        });

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);

        $exams = array_map(fn ($current) => Carbon::create($current['creation_time']), $response->json()['data']['exams']);

        for ($i = 1;$i < count($exams);$i ++) {
            $this->assertTrue($exams[$i]->lessThanOrEqualTo($exams[$i-1]));
        }
    }

    /**
    * @test
    */
    public function index_owned_exams_must_be_paginated()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Exam::factory()->count(20)->state([
            'published' => true
        ])->for($user)->create();
        Exam::factory()->count(20)->state([
            'published' => false
        ])->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'exams' => [
                    [
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
    public function another_user_will_see_his_owned_exams_and_can_not_see_another_users_exam_in_index_owned_exams()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Exam::factory()->state([
            'published' => true
        ])->count(30)->for($user)->create();

        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(200);

        $this->assertTrue($response->json()['data']['exams'] === []);
    }

    /**
    * @test
    */
    public function guest_user_can_not_request_to_index_owned_exams()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        Exam::factory()->state([
            'published' => true
        ])->count(30)->for($user)->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGOUT_ROUTE));

        $response->assertStatus(202);
        $this->app->get('auth')->forgetGuards();

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->get(route(self::INDEX_OWN_EXAM_ROUTE));

        $response->assertStatus(401);
    }
}
