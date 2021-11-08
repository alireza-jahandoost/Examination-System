<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// without middleware ; for guest and auth users
Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('current_user', [UserController::class, 'current_user'])->name('users.current');
});
