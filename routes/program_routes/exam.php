<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExamController;

// without middleware ; for guest and auth users
$middleware = 'api';
if (\Request::header('Authorization')) {
    $middleware = 'auth:sanctum';
}

Route::middleware($middleware)->group(function () {
    Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('exams', ExamController::class)->except(['index']);
    Route::get('own_exams', [ExamController::class, 'index_own'])->name('exams.own.index');
    Route::put('publish_exams/{exam}', [ExamController::class, 'publish'])->name('exams.publish');
    Route::put('unpublish_exams/{exam}', [ExamController::class, 'unpublish'])->name('exams.unpublish');
});
