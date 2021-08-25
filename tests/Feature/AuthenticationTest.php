<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Laravel\Sanctum\Sanctum;

use Illuminate\Support\Facades\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    const LOGIN_ROUTE = "authentication.login";
    const REGISTER_ROUTE = "authentication.register";

    /**
    * @test
    */
    public function a_user_can_register()
    {
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'xs$sl5T^23da',
            'password_confirmation' => 'xs$sl5T^23da',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('users', 1);
    }

    /**
    * @test
    */
    public function authenticated_user_can_not_reigster()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'xs$sl5T^23da',
            'password_confirmation' => 'xs$sl5T^23da',
        ]);

        $this->assertDatabaseCount('users', 1);
        $response->assertStatus(302);
    }

    /**
    * @test
    */
    public function password_must_be_more_than_or_equal_to_8_characters()
    {
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'xsl3$at',
            'password_confirmation' => 'xsl3$at',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('users', 0);
    }

    /**
    * @test
    */
    public function password_must_have_uppercase_and_lowercase_characters()
    {
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'alijewljrwa12',
            'password_confirmation' => 'alijewljrwa12',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('users', 0);
    }

    /**
    * @test
    */
    public function password_must_have_numeric_character()
    {
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'alijewlJrwa',
            'password_confirmation' => 'alijewlJrwa',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('users', 0);
    }

    /**
    * @test
    */
    public function personal_access_token_isnt_create_for_failed_registeration()
    {
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'alijewlJrwa',
            'password_confirmation' => 'alijewlJrwa',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
    * @test
    */
    public function password_has_not_been_compromised()
    {
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'Abcdefg123',
            'password_confirmation' => 'Abcdefg123',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('users', 0);
    }

    /**
    * @test
    */
    public function user_can_get_its_data_and_token_after_registering()
    {
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'Alfjr431JEx',
            'password_confirmation' => 'Alfjr431JEx',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $response->assertJson([
            'data' => [
                'user_id' => 1,
                'name' => 'test',
                'email' => 'test@test.com'
            ]
        ]);
        $response->assertJsonStructure([
            'data' => [
                'token',
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_will_receive_a_message_if_registering_was_unsuccessful()
    {
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'alijewlJrwa',
            'password_confirmation' => 'alijewlJrwa',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('users', 0);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);
    }

    /**
    * @test
    */
    public function user_can_login()
    {
        $user = User::factory()->create([
            'password' => Hash::make('aAlJT32LIfsli')
        ]);
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'aAlJT32LIfsli',
        ]);

        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function user_can_not_have_more_than_6_access_tokens()
    {
        $user = User::factory()->create([
            'password' => Hash::make('aAlJT32LIfsli')
        ]);
        for($i = 0; $i < 10; $i ++){
            $response = $this->withHeaders([
                    'Accept' => 'application/json',
                ])->post(route(self::LOGIN_ROUTE), [
                'email' => $user->email,
                'password' => 'aAlJT32LIfsli',
            ]);
            $response->assertStatus(200);
        }
        $this->assertDatabaseCount('personal_access_tokens', 6);
    }

    /**
    * @test
    */
    public function if_user_tokens_exceeds_from_limitation_first_token_will_be_removed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('aAlJT32LIfsli')
        ]);
        for($i = 0; $i < 7; $i ++){
            $response = $this->withHeaders([
                    'Accept' => 'application/json',
                ])->post(route(self::LOGIN_ROUTE), [
                'email' => $user->email,
                'password' => 'aAlJT32LIfsli',
            ]);
            $response->assertStatus(200);
        }
        $this->assertDatabaseCount('personal_access_tokens', 6);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => 1
        ]);
    }

    /**
    * @test
    */
    public function authenticated_user_can_not_login()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $user = User::factory()->create([
            'password' => Hash::make('aAlJT32LIfsli')
        ]);
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'aAlJT32LIfsli',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
    * @test
    */
    public function user_can_get_its_data_and_token_after_logging_in()
    {
        $user = User::factory()->create([
            'password' => Hash::make('aAlJT32LIfsli')
        ]);
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'aAlJT32LIfsli',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
        $response->assertJsonStructure([
            'data' => [
                'token',
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_cant_login_with_invalid_information()
    {
        $user = User::factory()->create([
            'password' => 'aAlJT32LIfsli'
        ]);
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => 'a'.$user->email,
            'password' => 'aAlJT32LIfsli',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
    * @test
    */
    public function user_cant_login_with_incorrect_password()
    {
        $user = User::factory()->create([
            'password' => 'aAlJT32LIfsli'
        ]);
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'aaAlJT32LIfsli',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
    * @test
    */
    public function user_will_receive_a_message_if_login_was_unsuccessful()
    {
        $user = User::factory()->create([
            'password' => 'aAlJT32LIfsli'
        ]);
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => 'a'.$user->email,
            'password' => 'aAlJT32LIfsli',
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'data' => [
                'message'
            ]
        ]);
    }

}
