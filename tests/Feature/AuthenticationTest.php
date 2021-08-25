<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

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
        $this->assertDatabaseCount('personal_access_tokens', 1);
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
        $this->assertDatabaseCount('personal_access_tokens', 0);
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
        $this->assertDatabaseCount('personal_access_tokens', 0);
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
        $this->assertDatabaseCount('personal_access_tokens', 0);
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

        // $token = User::first()->tokens->first()->token;
        // dd($token);

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
        $this->assertDatabaseCount('personal_access_tokens', 0);
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
