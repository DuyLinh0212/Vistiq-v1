<?php

use Illuminate\Support\Facades\Route;
use VistiqAdmin\Http\Controllers\Admin\System\SettingsController;
use VistiqAdmin\Http\Controllers\Admin\System\DatabaseBackupController;

Route::get('/settings', [SettingsController::class, 'index'])->middleware('can:settings.manage')->name('settings.index');
Route::put('/settings/{group}', [SettingsController::class, 'update'])->middleware(['can:settings.manage', 'throttle:20,1'])->name('settings.update');
Route::get('/settings/database', [DatabaseBackupController::class, 'show'])->middleware('can:settings.manage')->name('settings.database');
Route::post('/settings/database/backup', [DatabaseBackupController::class, 'download'])->middleware(['can:settings.manage', 'throttle:3,1'])->name('settings.database.backup');
Route::post('/settings/database/restore', [DatabaseBackupController::class, 'restore'])->middleware(['can:settings.manage', 'throttle:1,1'])->name('settings.database.restore');
Route::get('/audit', [SettingsController::class, 'audit'])->middleware('can:audit.view')->name('audit.index');
Route::post('/ai', [SettingsController::class, 'ai'])->middleware(['can:content.edit', 'throttle:6,1'])->name('ai.create');
Route::get('/ai/{task}', [SettingsController::class, 'aiStatus'])->name('ai.show');
