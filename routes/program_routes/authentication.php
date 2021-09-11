<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;

Route::middleware('guest:sanctum')->group(function () {
    Route::post('register', [AuthenticationController::class, 'register'])->name('register');
    Route::post('login', [AuthenticationController::class, 'login'])->name('login');

    Route::post('password_reset_link', [AuthenticationController::class, 'password_reset_link'])->name('password.reset_link');
    Route::put('password_reset', [AuthenticationController::class, 'password_reset'])->name('password.reset');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::put('change_password', [AuthenticationController::class, 'change_password'])->name('password.change');
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
});
