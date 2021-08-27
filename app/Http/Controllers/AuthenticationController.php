<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\PasswordReset;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;

use App\Http\Resources\AuthenticationResource;
use App\Http\Resources\MessageResource;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\EmailValidationRequest;


use App\Models\User;

class AuthenticationController extends Controller
{

    const TOKEN_COUNT_LIMIT = 6;

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

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if(!$user || !Hash::check($data['password'], $user->password)){
            return (new MessageResource([
                'message' => 'Invalid email or password',
                ]))->response()->setStatusCode(401);
        }

        if($user->tokens()->count() >= self::TOKEN_COUNT_LIMIT){
            $user->tokens()->first()->delete();
        }

        return self::make_token_and_make_response($user, 200);
    }

    public function password_reset_link(EmailValidationRequest $request)
    {
        if(User::where('email', $request->input('email'))->exists()){
            $status = Password::sendResetLink($request->only('email'));
        }

        return (new MessageResource([
                'message' => 'if user with this email exists, password recovery link has been sent',
            ]))->response()->setStatusCode(200);
    }

    public function password_reset(PasswordResetRequest $request, ResetUserPassword $action)
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->firstOrFail();

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);

                $user->save();
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if($status === Password::PASSWORD_RESET){
            return (new MessageResource([
                'message' => 'password changed successfully'
            ]))->response()->setStatusCode(200);
        }
    }

    public function change_password(Request $request, UpdateUserPassword $action)
    {
        $action->update(auth()->user(), $request->input());

        auth()->user()->tokens()->delete();

        return (new MessageResource([
            'message' => 'password changed successfully'
        ]))->response()->setStatusCode(200);
    }
}
