<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function show(User $user)
    {
        return (new UserResource($user))->response()->setStatusCode(200);
    }

    public function current_user()
    {
        return (new UserResource(auth()->user()))->response()->setStatusCode(200);
    }
}
