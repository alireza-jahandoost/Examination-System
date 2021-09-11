<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuestionTypeController;

// without middleware ; for guest and auth users
Route::get('question_types', [QuestionTypeController::class, 'index'])->name('question_types.index');
Route::get('question_types/{questionType:slug}', [QuestionTypeController::class, 'show'])->name('question_types.show');
