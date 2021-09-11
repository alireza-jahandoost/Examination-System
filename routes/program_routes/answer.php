<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnswerController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('questions/{question}/answers', AnswerController::class)->only(['store']);
    Route::get('questions/{question}/participants/{participant}/answers', [AnswerController::class, 'index'])->name('answers.index');
    Route::delete('questions/{question}/answers', [AnswerController::class, 'destroy'])->name('answers.destroy');
});
