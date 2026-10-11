<?php

use Illuminate\Support\Facades\Route;
use VistiqAdmin\Http\Controllers\Admin\Analytics\DashboardController;
use VistiqAdmin\Http\Controllers\Admin\Analytics\LeaderboardController;

Route::get('/', [DashboardController::class, 'index'])->middleware('can:admin.access')->name('dashboard');
Route::get('/analytics', [DashboardController::class, 'analytics'])->middleware('can:results.view')->name('analytics');
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->middleware('can:results.view')->name('leaderboard.index');
Route::post('/leaderboard/{exam}/reset', [LeaderboardController::class, 'reset'])->middleware('can:content.publish')->name('leaderboard.reset');
