<?php

use Illuminate\Support\Facades\Route;
use VistiqLearner\Http\Controllers\Auth\AuthController;
use VistiqLearner\Http\Controllers\DemoPageController;
use VistiqLearner\Http\Controllers\Dictionary\LookupController;
use VistiqLearner\Http\Controllers\Practice\AttemptRunnerController;
use VistiqLearner\Http\Controllers\Site\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/lookup', [LookupController::class, 'index'])->middleware('throttle:60,1')->name('lookup.index');
Route::get('/lookup/{slug}', [LookupController::class, 'entry'])->middleware('throttle:60,1')->name('lookup.entry');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:6,1')->name('login.store');

    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/forgot-password', [AuthController::class, 'forgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/demo/{screen}', [DemoPageController::class, 'show'])->name('demo.page');

Route::middleware(['auth', 'active.learner'])->group(function (): void {
    Route::get('/exams', [AttemptRunnerController::class, 'index'])->name('exams.index');
    Route::post('/exams/{exam}/start', [AttemptRunnerController::class, 'start'])->middleware('throttle:10,1')->name('exams.start');
    Route::get('/attempts/{attempt}', [AttemptRunnerController::class, 'show'])->name('attempts.show');
    Route::get('/attempts/{attempt}/media/{asset}', [AttemptRunnerController::class, 'media'])->name('attempts.media');
    Route::get('/attempts/{attempt}/state', [AttemptRunnerController::class, 'state'])->name('attempts.state');
    Route::get('/attempts/{attempt}/result', [AttemptRunnerController::class, 'result'])->name('attempts.result');
    foreach (['answers', 'events', 'pause', 'resume', 'section', 'submit', 'recording', 'audio'] as $action) {
        Route::post('/attempts/{attempt}/'.$action, [AttemptRunnerController::class, $action])->middleware('throttle:120,1')->name('attempts.'.$action);
    }
});
