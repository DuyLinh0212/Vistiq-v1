<?php

use Illuminate\Support\Facades\Route;
use VistiqLearner\Http\Controllers\Knowledge\GrammarController;
use VistiqLearner\Http\Controllers\Knowledge\KnowledgeController;

Route::get('/grammar', [GrammarController::class, 'index'])->name('grammar.index');
Route::get('/grammar/{slug}', [GrammarController::class, 'detail'])->name('grammar.show');

Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
Route::get('/knowledge/saved', [KnowledgeController::class, 'savedList'])->name('knowledge.saved');
Route::get('/knowledge/{slug}', [KnowledgeController::class, 'detail'])->name('knowledge.show');
