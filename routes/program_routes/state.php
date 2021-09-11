<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StateController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('exams/{exam}/questions/{question}/states', StateController::class);
});
