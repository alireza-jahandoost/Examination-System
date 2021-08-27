<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExamController;

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

Route::middleware('guest')->prefix('authentication')->name('authentication.')->group(function(){
    Route::post('register', [AuthenticationController::class, 'register'])->name('register');
    Route::post('login', [AuthenticationController::class, 'login'])->name('login');

    Route::post('password_reset_link', [AuthenticationController::class, 'password_reset_link'])->name('password.reset_link');
    Route::put('password_reset', [AuthenticationController::class, 'password_reset'])->name('password.reset');
});

Route::middleware('auth:sanctum')->prefix('authentication')->name('authentication.')->group(function(){
    Route::put('change_password', [AuthenticationController::class, 'change_password'])->name('password.change');
});

Route::middleware('api')->group(function(){
    Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
    Route::get('exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
});

Route::middleware('auth:sanctum')->group(function(){
    Route::apiResource('exams', ExamController::class)->except(['index', 'show']);
});
