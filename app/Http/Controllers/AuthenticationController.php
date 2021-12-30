<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
    /**
     * limit of tokens that a user can have
     * @var integer
     */
    public const TOKEN_COUNT_LIMIT = 6;

    /**
     * @param  User   $user
     * @param  int    $status
     */
    private static function make_token_and_make_response(User $user, int $status)
    {
        $token = $user->createToken('SanctumAuthenticationToken');
        return (new AuthenticationResource([
            'user' => $user,
            'token' => $token->plainTextToken,
            ]))->response()->setStatusCode($status);
    }

    /**
     * register a new user
     * @param  Request                $request
     * @param  CreateNewUser          $action
     */
    public function register(Request $request, CreateNewUser $action)
    {
        $user = $action->create($request->input());
        return self::make_token_and_make_response($user, 201);
    }

    /**
     * login a user
     * @param  LoginRequest           $request
     */
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', strtolower($data['email']))->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => "the given data was invalid.",
                'errors' => [
                    'email' => ['Invalid email or password']
                ]
            ], 401);
        }

        if ($user->tokens()->count() >= self::TOKEN_COUNT_LIMIT) {
            $user->tokens()->first()->delete();
        }

        return self::make_token_and_make_response($user, 200);
    }

    /**
     * send a link for reseting password
     * @param EmailValidationRequest $request
     */
    public function password_reset_link(EmailValidationRequest $request)
    {
        if (User::where('email', $request->input('email'))->exists()) {
            $status = Password::sendResetLink($request->only('email'));
        }

        return (new MessageResource([
                'message' => 'if user with this email exists, password recovery link has been sent',
            ]))->response()->setStatusCode(200);
    }

    /**
     * reset user's password
     * @param PasswordResetRequest $request
     * @param ResetUserPassword    $action
     */
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

        if ($status === Password::PASSWORD_RESET) {
            return (new MessageResource([
                'message' => 'password changed successfully'
            ]))->response()->setStatusCode(200);
        }
    }

    /**
     * change user's password
     * @param  Request            $request
     * @param  UpdateUserPassword $action
     */
    public function change_password(Request $request, UpdateUserPassword $action)
    {
        $action->update(auth()->user(), $request->input());

        auth()->user()->tokens()->delete();

        return (new MessageResource([
            'message' => 'password changed successfully'
        ]))->response()->setStatusCode(200);
    }

    /**
     * logout the user
     * @param  Request $request
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response(null, 202);
    }
}
