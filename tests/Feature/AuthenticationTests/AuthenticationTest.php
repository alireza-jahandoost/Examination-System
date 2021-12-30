<?php

namespace Tests\Feature\AuthenticationTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Laravel\Sanctum\Sanctum;

use Illuminate\Support\Facades\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public const LOGIN_ROUTE = "authentication.login";
    public const REGISTER_ROUTE = "authentication.register";
    public const LOGOUT_ROUTE = "authentication.logout";

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
    public function in_registration_email_must_be_saved_lower_case()
    {
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::REGISTER_ROUTE), [
            'name' => 'test',
            'email' => 'tEsT@tEst.coM',
            'password' => 'xs$sl5T^23da',
            'password_confirmation' => 'xs$sl5T^23da',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'test@test.com'
        ]);
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
                'user' => [
                    'user_id' => 1,
                    'user_name' => 'test',
                    'user_email' => 'test@test.com'
                ]
            ]
        ]);
        $response->assertJsonStructure([
            'data' => [
                'token',
                'user' => [
                    'user_register_time'
                ]
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
    public function in_login_email_must_not_be_case_sensitive()
    {
        $user = User::factory()->create([
            'password' => Hash::make('aAlJT32LIfsli'),
            'email' => 'test@test.com',
        ]);
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => 'TeSt@tEst.cOm',
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
        for ($i = 0; $i < 10; $i ++) {
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
        for ($i = 0; $i < 7; $i ++) {
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
                'user' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                ]
            ]
        ]);
        $response->assertJsonStructure([
            'data' => [
                'token',
                'user' => [
                    'user_register_time'
                ]
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
            'message',
            'errors' => [
                'email'
            ]
        ]);
    }

    /**
    * @test
    */
    public function an_authenticated_user_can_logout()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::LOGOUT_ROUTE));
        $response->assertStatus(202);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
    * @test
    */
    public function a_user_can_login_and_logout()
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
        ]);
        $login_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => 'test@test.com',
            'password' => 'password'
        ]);
        $token = $login_response->json()['data']['token'];
        $login_response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $logout_response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token
            ])->post(route(self::LOGOUT_ROUTE));
        $logout_response->assertStatus(202);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
    * @test
    */
    public function after_logout_just_the_logged_in_token_will_be_removed()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create([
            'email' => 'test@test.com',
        ]);
        $login_response = $this->withHeaders([
            'Accept' => 'application/json'
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => 'test@test.com',
            'password' => 'password'
        ]);
        $token = $login_response->json()['data']['token'];
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $login_response2 = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::LOGIN_ROUTE), [
            'email' => 'test@test.com',
            'password' => 'password'
        ]);
        $token2 = $login_response2->json()['data']['token'];
        $this->assertDatabaseCount('personal_access_tokens', 2);

        $logout_response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token
            ])->post(route(self::LOGOUT_ROUTE));
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $logout_response->assertStatus(202);

        $this->app->get('auth')->forgetGuards();

        $logout_response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token2
            ])->post(route(self::LOGOUT_ROUTE));
        $logout_response->assertStatus(202);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
    * @test
    */
    public function a_guest_user_can_not_logout()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])->post(route(self::LOGOUT_ROUTE));
        $response->assertStatus(401);
    }
}
