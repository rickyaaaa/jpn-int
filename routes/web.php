<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\InterviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InterviewController::class, 'start'])->name('start');
Route::post('/login', [InterviewController::class, 'login'])->name('candidate.login');
Route::post('/logout', [InterviewController::class, 'logout'])->name('logout');
Route::get('/interview', [InterviewController::class, 'interview'])->name('interview');
Route::get('/answers', [InterviewController::class, 'answers'])->name('answers.index');
Route::post('/answers', [InterviewController::class, 'storeAnswer'])->name('answers.store');
Route::get('/results', [InterviewController::class, 'results'])->name('results');

Route::get('/admin/login', [AdminController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login');

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/generate-token', [AdminController::class, 'generateToken'])->name('admin.generate-token');
    Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
});
