<?php

use App\Http\Controllers\InterviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InterviewController::class, 'start'])->name('start');
Route::post('/login', [InterviewController::class, 'login'])->name('login');
Route::post('/logout', [InterviewController::class, 'logout'])->name('logout');
Route::get('/admin/generate-token', [InterviewController::class, 'generateToken'])->name('admin.generate-token');
Route::get('/interview', [InterviewController::class, 'interview'])->name('interview');
Route::get('/answers', [InterviewController::class, 'answers'])->name('answers.index');
Route::post('/answers', [InterviewController::class, 'storeAnswer'])->name('answers.store');
Route::get('/results', [InterviewController::class, 'results'])->name('results');
