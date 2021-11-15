<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ParticipantController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('exams/{exam}/register', [ParticipantController::class, 'store'])->name('exams.register');
    Route::put('accept_participant/{exam}', [ParticipantController::class, 'update'])->name('exams.accept_user');
    Route::get('exams/{exam}/participants', [ParticipantController::class, 'index'])->name('participants.index');
    Route::get('/participants/{participant}/questions/{question}/get_question_grade', [ParticipantController::class, 'question_grade'])->name('participants.grade.question');
    Route::get('exams/{exam}/participants/{participant}', [ParticipantController::class, 'show'])->name('participants.show');
    Route::put('exams/{exam}/finish_exam', [ParticipantController::class, 'finish_exam'])->name('participants.finish_exam');
    Route::post('/questions/{question}/participants/{participant}/save_score', [ParticipantController::class, 'save_score'])->name('participants.save_score');
    Route::get('participated_exams', [ParticipantController::class, 'participated_exams'])->name('participants.participated_exams');
    Route::get('exams/{exam}/current_participant', [ParticipantController::class, 'current_participant'])->name('participants.current');
});
