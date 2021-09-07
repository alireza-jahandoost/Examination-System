<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionTypeController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\AnswerController;

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

// Authentication Routes
Route::middleware('guest:sanctum')->prefix('authentication')->name('authentication.')->group(function(){
    Route::post('register', [AuthenticationController::class, 'register'])->name('register');
    Route::post('login', [AuthenticationController::class, 'login'])->name('login');

    Route::post('password_reset_link', [AuthenticationController::class, 'password_reset_link'])->name('password.reset_link');
    Route::put('password_reset', [AuthenticationController::class, 'password_reset'])->name('password.reset');
});

// Authentication Routes
Route::middleware('auth:sanctum')->prefix('authentication')->name('authentication.')->group(function(){
    Route::put('change_password', [AuthenticationController::class, 'change_password'])->name('password.change');
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
});

// Program routes that dont need authentication to see
Route::middleware('api')->group(function(){
    // Exams
    Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
    Route::get('exams/{exam}', [ExamController::class, 'show'])->name('exams.show');

    //Question Types
    Route::get('question_types', [QuestionTypeController::class, 'index'])->name('question_types.index');
    Route::get('question_types/{questionType:slug}', [QuestionTypeController::class, 'show'])->name('question_types.show');
});

// Program routes that need authentication to see
Route::middleware('auth:sanctum')->group(function(){
    // Exam Routes
    Route::apiResource('exams', ExamController::class)->except(['index', 'show']);
    Route::get('own_exams', [ExamController::class, 'index_own'])->name('exams.own.index');
    Route::post('publish_exams/{exam}', [ExamController::class, 'publish'])->name('exams.publish');
    Route::post('unpublish_exams/{exam}', [ExamController::class, 'unpublish'])->name('exams.unpublish');

    // Question Routes
    Route::apiResource('exams/{exam}/questions', QuestionController::class);

    // State Routes
    Route::apiResource('exams/{exam}/questions/{question}/states', StateController::class);

    // Participant Routes
    Route::post('exams/{exam}/register', [ParticipantController::class, 'store'])->name('exams.register');
    Route::put('accept_participant/{exam}', [ParticipantController::class, 'update'])->name('exams.accept_user');
    Route::get('exams/{exam}/participants', [ParticipantController::class, 'index'])->name('participants.index');
    Route::get('/participants/{participant}/questions/{question}/get_question_grade', [ParticipantController::class, 'question_grade'])->name('participants.grade.question');
    Route::get('exams/{exam}/participants/{participant}', [ParticipantController::class, 'show'])->name('participants.show');
    Route::put('exams/{exam}/finish_exam', [ParticipantController::class, 'finish_exam'])->name('participants.finish_exam');
    Route::post('/questions/{question}/participants/{participant}/save_score', [ParticipantController::class, 'save_score'])->name('participants.save_score');

    // Answer Routes
    Route::apiResource('questions/{question}/answers', AnswerController::class)->only(['store']);
    Route::get('questions/{question}/participants/{participant}/answers', [AnswerController::class, 'index'])->name('answers.index');
    Route::delete('questions/{question}/answers', [AnswerController::class, 'destroy'])->name('answers.destroy');
});
