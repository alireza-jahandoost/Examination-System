<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuestionController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('exams/{exam}/questions', QuestionController::class);
});
