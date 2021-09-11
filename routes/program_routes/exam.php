<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExamController;

// without middleware ; for guest and auth users
Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
Route::get('exams/{exam}', [ExamController::class, 'show'])->name('exams.show');


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('exams', ExamController::class)->except(['index', 'show']);
    Route::get('own_exams', [ExamController::class, 'index_own'])->name('exams.own.index');
    Route::put('publish_exams/{exam}', [ExamController::class, 'publish'])->name('exams.publish');
    Route::put('unpublish_exams/{exam}', [ExamController::class, 'unpublish'])->name('exams.unpublish');
});
