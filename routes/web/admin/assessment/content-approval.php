<?php

use Illuminate\Support\Facades\Route;
use VistiqAdmin\Http\Controllers\Admin\Assessment\ContentApproval\ContentApprovalController;
use VistiqAdmin\Http\Controllers\Admin\Assessment\ContentApproval\ReviewQueueController;

Route::get('/content-approval', [ReviewQueueController::class, 'index'])->middleware('can:content.review')->name('content-approval.index');
Route::get('/content-approval/{kind}/{id}', [ReviewQueueController::class, 'show'])->whereIn('kind', ['question', 'exam'])->middleware(['can:content.review', 'can:content.view'])->name('content-approval.show');
Route::post('/content-approval/{kind}/{id}/{action}', [ReviewQueueController::class, 'decide'])->whereIn('kind', ['question', 'exam'])->whereIn('action', ['approve', 'reject'])->middleware(['can:content.review', 'throttle:30,1'])->name('content-approval.decide');

Route::post('/content/{kind}/{id}/clone-draft', [ContentApprovalController::class, 'cloneDraft'])->middleware('can:content.edit')->name('content.clone');
Route::delete('/content/{kind}/{id}', [ContentApprovalController::class, 'destroy'])->middleware('can:content.edit')->name('content.destroy');
Route::post('/content/{kind}/{id}/{action}', [ContentApprovalController::class, 'transition'])->whereIn('action', ['review', 'approve', 'reject', 'publish', 'archive'])->middleware('throttle:30,1')->name('content.transition');
Route::post('/exams/{exam}/{action}', [ContentApprovalController::class, 'transitionExam'])->whereIn('action', ['review', 'approve', 'reject', 'publish', 'archive'])->middleware('throttle:30,1')->name('exams.transition');
