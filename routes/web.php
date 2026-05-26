<?php

use App\Http\Controllers\InterviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InterviewController::class, 'start'])->name('start');
Route::post('/login', [InterviewController::class, 'login'])->name('login');
Route::post('/logout', [InterviewController::class, 'logout'])->name('logout');
Route::get('/interview', [InterviewController::class, 'interview'])->name('interview');
Route::post('/answers', [InterviewController::class, 'storeAnswer'])->name('answers.store');
Route::get('/results', [InterviewController::class, 'results'])->name('results');
