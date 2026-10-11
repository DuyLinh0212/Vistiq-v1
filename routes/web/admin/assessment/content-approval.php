<?php

use Illuminate\Support\Facades\Route;
use VistiqAdmin\Http\Controllers\Admin\Assessment\ContentApproval\ContentApprovalController;

Route::post('/content/{kind}/{id}/clone-draft', [ContentApprovalController::class, 'cloneDraft'])->middleware('can:content.edit')->name('content.clone');
Route::delete('/content/{kind}/{id}', [ContentApprovalController::class, 'destroy'])->middleware('can:content.edit')->name('content.destroy');
Route::post('/content/{kind}/{id}/{action}', [ContentApprovalController::class, 'transition'])->whereIn('action', ['review', 'approve', 'reject', 'publish', 'archive'])->middleware('throttle:30,1')->name('content.transition');
Route::post('/exams/{exam}/{action}', [ContentApprovalController::class, 'transitionExam'])->whereIn('action', ['review', 'approve', 'reject', 'publish', 'archive'])->middleware('throttle:30,1')->name('exams.transition');
