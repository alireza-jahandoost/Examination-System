<?php

namespace Tests\Feature\UserTests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Laravel\Sanctum\Sanctum;

use App\Models\User;

class AuthenticateTokenTest extends TestCase
{
    use RefreshDatabase;

    public const CURRENT_USER_ROUTE = 'users.current';

    /**
     * @test
     */
    public function user_can_get_his_information_if_authenticated()
    {
        Sanctum::actingAs(
            $user = User::factory()->create(),
            ['*']
        );

        $response = $this->withHeaders(['Accept' => 'application/json'])->get(route(self::CURRENT_USER_ROUTE));

        $response->assertStatus(200);
        $response->assertJson([
             'data' => [
                 'user' => [
                     'user_id' => $user->id,
                 ]
             ]
         ]);
    }

    /**
     * @test
     */
    public function if_user_is_not_authenticated_user_will_receive_401_error()
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])->get(route(self::CURRENT_USER_ROUTE));

        $response->assertStatus(401);
    }
}
