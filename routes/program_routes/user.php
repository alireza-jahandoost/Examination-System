<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// without middleware ; for guest and auth users
Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
