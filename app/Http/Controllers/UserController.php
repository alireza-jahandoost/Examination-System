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
}
