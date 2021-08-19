<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Resources\AuthenticationResource;
use App\Http\Resources\FailedLoginResource;

use App\Models\User;

class AuthenticationController extends Controller
{
    private static function make_token_and_make_response(User $user, $status)
    {
        $token = $user->createToken('token');
        return (new AuthenticationResource([
            'user' => $user,
            'token' => $token,
            ]))->response()->setStatusCode($status);
    }
    public function register(Request $request, CreateNewUser $action)
    {
        $user = $action->create($request->input());
        return self::make_token_and_make_response($user, 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $data['email'])->first();
        if(!$user || !Hash::check($data['password'], $user->password)){
            return (new FailedLoginResource([
                'message' => 'Invalid email or password',
                ]))->response()->setStatusCode(401);
        }
        return self::make_token_and_make_response($user, 200);
    }
}
