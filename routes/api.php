<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('guest')->prefix('authentication')->name('authentication.')->group(function(){
    Route::post('register', [AuthenticationController::class, 'register'])->name('register');
    Route::post('login', [AuthenticationController::class, 'login'])->name('login');

    Route::post('password_reset_link', [AuthenticationController::class, 'password_reset_link'])->name('password.reset_link');
    Route::put('password_reset', [AuthenticationController::class, 'password_reset'])->name('password.reset');
});

Route::middleware('auth:sanctum')->prefix('authentication')->name('authentication.')->group(function(){
    Route::put('change_password', [AuthenticationController::class, 'change_password'])->name('password.change');
});

Route::middleware('auth:sanctum')->group(function(){
    // Route::Apiresource('/users', UserController::class);
});
