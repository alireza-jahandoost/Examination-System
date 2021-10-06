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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public const PASSWORD_RESET_LINK_ROUTE = "authentication.password.reset_link";
    public const PASSWORD_RESET_ROUTE = "authentication.password.reset";
    public const PASSWORD_CHANGE_ROUTE = "authentication.password.change";
    public const LOGIN_ROUTE = "authentication.login";

    /**
    * @test
    */
    public function user_can_request_for_reset_its_password()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();

        $user = User::factory()->create();
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::PASSWORD_RESET_LINK_ROUTE), [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function authenticated_user_can_not_request_for_resetting_password()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $user = User::factory()->create();
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::PASSWORD_RESET_LINK_ROUTE), [
            'email' => $user->email,
        ]);

        $response->assertStatus(302);
        Notification::assertNothingSent();
    }

    /**
    * @test
    */
    public function user_will_receive_an_email_after_reset_password_request()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();

        $user = User::factory()->create();
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::PASSWORD_RESET_LINK_ROUTE), [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    /**
    * @test
    */
    public function if_user_email_is_not_available_reset_email_wont_send()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();

        $user = User::factory()->create();
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::PASSWORD_RESET_LINK_ROUTE), [
            'email' => 'a'.$user->email,
        ]);

        $response->assertStatus(200);
        Notification::assertNothingSent();
    }

    /**
    * @test
    */
    public function for_requesting_reset_password_email_must_be_valid()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();

        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::PASSWORD_RESET_LINK_ROUTE), [
            'email' => 'test',
        ]);

        $response->assertStatus(422);
        Notification::assertNothingSent();
    }

    /**
    * @test
    */
    public function user_will_receive_a_message_after_requesting_for_reset_password()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();

        $user = User::factory()->create();
        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::PASSWORD_RESET_LINK_ROUTE), [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'message',
            ]
        ]);
    }

    /**
    * @test
    */
    public function if_reset_password_email_didnt_send_the_user_will_see_same_message_as_when_it_sent()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();

        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->post(route(self::PASSWORD_RESET_LINK_ROUTE), [
            'email' => 'test@test.com',
        ]);

        $response->assertStatus(200);
        Notification::assertNothingSent();
        $response->assertJsonStructure([
            'data' => [
                'message',
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_can_send_reset_form_and_reset_his_or_her_password()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlle31F',
            'password_confirmation' => 'xgrntlle31F',
            'token' => $token
        ]);

        $user->refresh();
        $this->assertTrue($user->password !== $first_password);
    }

    /**
    * @test
    */
    public function user_will_receive_a_message_if_his_password_reset_successfully()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlle31F',
            'password_confirmation' => 'xgrntlle31F',
            'token' => $token
        ]);
        $response->assertJsonStructure([
            'data' => [
                'message'
            ]
        ]);
    }

    /**
    * @test
    */
    public function after_resetting_password_status_code_must_be_200()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlle31F',
            'password_confirmation' => 'xgrntlle31F',
            'token' => $token
        ]);
        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function for_reseting_password_email_of_user_must_exist_in_database()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => 'a' . $user->email,
            'password' => 'xgrntlle31F',
            'password_confirmation' => 'xgrntlle31F',
            'token' => $token
        ]);
        $user->refresh();
        $response->assertStatus(422);
        $this->assertTrue($user->password === $first_password);
    }

    /**
    * @test
    */
    public function if_password_reset_failed_user_must_see_an_error()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => 'a' . $user->email,
            'password' => 'xgrntlle31F',
            'password_confirmation' => 'xgrntlle31F',
            'token' => $token
        ]);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);
    }

    /**
    * @test
    */
    public function for_reseting_password_token_must_be_valid()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlle31F',
            'password_confirmation' => 'xgrntlle31F',
            'token' => 'a' . $token
        ]);
        $user->refresh();
        $this->assertTrue($user->password === $first_password);
    }

    /**
    * @test
    */
    public function for_reseting_password_password_and_password_confirmation_must_match()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlle31F',
            'password_confirmation' => 'xgarntlle31F',
            'token' => $token
        ]);
        $user->refresh();
        $response->assertStatus(422);
        $this->assertFalse($user->password !== $first_password);
    }

    /**
    * @test
    */
    public function when_requesting_new_password_it_must_have_lower_and_upper_characters()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlle31t',
            'password_confirmation' => 'xgrntlle31t',
            'token' => $token
        ]);
        $user->refresh();
        $response->assertStatus(422);
        $this->assertTrue($user->password === $first_password);
    }

    /**
    * @test
    */
    public function when_requesting_new_password_it_must_have_numeric_character()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlleYt',
            'password_confirmation' => 'xgrntlleYt',
            'token' => $token
        ]);
        $user->refresh();
        $response->assertStatus(422);
        $this->assertFalse($user->password !== $first_password);
    }

    /**
    * @test
    */
    public function when_requesting_new_password_it_must_be_longer_than_7_characters()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xS14dFs',
            'password_confirmation' => 'xS14dFs',
            'token' => $token
        ]);
        $user->refresh();
        $response->assertStatus(422);
        $this->assertFalse($user->password !== $first_password);
    }

    /**
    * @test
    */
    public function user_must_be_logged_out_from_all_of_its_accounts_when_his_or_her_password_reset()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlle31F',
            'password_confirmation' => 'xgrntlle31F',
            'token' => $token
        ]);
        $user->refresh();
        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue($user->password !== $first_password);
    }

    /**
    * @test
    */
    public function password_reset_event_must_be_fired_after_resetting_password()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlle31F',
            'password_confirmation' => 'xgrntlle31F',
            'token' => $token
        ]);
        $user->refresh();
        $this->assertTrue($user->password  !== $first_password);
        $response->assertStatus(200);
        Event::assertDispatched(PasswordReset::class);
    }

    /**
    * @test
    */
    public function when_reseting_password_user_will_see_error_if_his_password_wasnt_strong_enough()
    {
        Mail::fake();
        Notification::fake();
        Event::fake();
        $user = User::factory()->create();
        $first_password = $user->password;

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->put(route(self::PASSWORD_RESET_ROUTE), [
            'email' => $user->email,
            'password' => 'xgrntlle31',
            'password_confirmation' => 'xgrntlle31',
            'token' => $token
        ]);
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);
    }

    /**
    * @test
    */
    public function user_is_allowed_to_change_his_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'email' => $user->email,
            'current_password' => 'password',
            'password' => 'a$ltnei31laA',
            'password_confirmation' => 'a$ltnei31laA',
        ]);

        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function unauthenticated_user_can_not_change_any_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'email' => $user->email,
            'current_password' => 'password',
            'password' => 'a$ltnei31laA',
            'password_confirmation' => 'a$ltnei31laA',
        ]);

        $response->assertStatus(401);
    }

    /**
    * @test
    */
    public function user_can_change_his_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'password',
            'password' => 'a$ltnei31laA',
            'password_confirmation' => 'a$ltnei31laA',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('a$ltnei31laA', $user->password));
    }

    /**
    * @test
    */
    public function user_will_receive_a_message_after_changing_his_password_successfully()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'password',
            'password' => 'a$ltnei31laA',
            'password_confirmation' => 'a$ltnei31laA',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('a$ltnei31laA', $user->password));
        $response->assertJsonStructure([
            'data' => [
                'message'
            ]
        ]);
    }

    /**
    * @test
    */
    public function user_will_receive_status_code_200_after_changing_his_password_successfully()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'password',
            'password' => 'a$ltnei31laA',
            'password_confirmation' => 'a$ltnei31laA',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('a$ltnei31laA', $user->password));
        $response->assertStatus(200);
    }

    /**
    * @test
    */
    public function for_changing_password_password_and_password_confirmation_must_be_equal()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'password',
            'password' => '$ltnei31laA',
            'password_confirmation' => 'a$ltnei31laA',
        ]);

        $user->refresh();
        $this->assertFalse(Hash::check('$ltnei31laA', $user->password));
        $response->assertStatus(422);
    }

    /**
    * @test
    */
    public function for_changing_password_current_password_must_be_right()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'passwor',
            'password' => 'a$ltnei31laA',
            'password_confirmation' => 'a$ltnei31laA',
        ]);

        $user->refresh();
        $this->assertFalse(Hash::check('a$ltnei31laA', $user->password));
        $response->assertStatus(422);
    }

    /**
    * @test
    */
    public function for_changing_password_it_must_have_lowercaase_and_uppercase_letters()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'password',
            'password' => 'a$ltnei31la',
            'password_confirmation' => 'a$ltnei31la',
        ]);

        $user->refresh();
        $this->assertFalse(Hash::check('a$ltnei31la', $user->password));
        $response->assertStatus(422);
    }

    /**
    * @test
    */
    public function for_changing_password_password_must_be_longer_than_7_characters()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'password',
            'password' => 'a376lLI',
            'password_confirmation' => 'a376lLI',
        ]);

        $user->refresh();
        $this->assertFalse(Hash::check('a376lLI', $user->password));
        $response->assertStatus(422);
    }

    /**
    * @test
    */
    public function for_changing_password_it_must_have_numeric_character()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'password',
            'password' => 'a$ltneilaA',
            'password_confirmation' => 'a$ltneilaA',
        ]);

        $user->refresh();
        $this->assertFalse(Hash::check('a$ltneilaA', $user->password));
        $response->assertStatus(422);
    }

    /**
    * @test
    */
    public function user_will_receive_a_message_if_changing_password_was_unsuccessfull()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'password',
            'password' => 'a$ltneilaA',
            'password_confirmation' => 'a$ltneilaA',
        ]);

        $user->refresh();
        $this->assertFalse(Hash::check('a$ltneilaA', $user->password));
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);
    }

    /**
    * @test
    */
    public function user_will_be_logged_out_from_all_of_its_accounts_after_changing_his_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $login_response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post(route(self::LOGIN_ROUTE), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login_response->json()['data']['token'];

        $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->put(route(self::PASSWORD_CHANGE_ROUTE), [
            'current_password' => 'password',
            'password' => 'a$ltnei31laA',
            'password_confirmation' => 'a$ltnei31laA',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('a$ltnei31laA', $user->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $response->assertStatus(200);
    }
}
