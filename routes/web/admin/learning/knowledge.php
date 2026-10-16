<?php

use Illuminate\Support\Facades\Route;
use VistiqAdmin\Http\Controllers\Admin\Learning\Knowledge\KnowledgeAdminController;

Route::middleware('can:content.view')->group(function (): void {
    Route::get('/knowledge/export', [KnowledgeAdminController::class, 'export'])->name('knowledge.export');
});

Route::middleware('can:content.edit')->group(function (): void {
    Route::get('/knowledge/import', [KnowledgeAdminController::class, 'importForm'])->name('knowledge.import');
    Route::post('/knowledge/import/preview', [KnowledgeAdminController::class, 'importPreview'])->name('knowledge.import.preview');
    Route::post('/knowledge/import/confirm', [KnowledgeAdminController::class, 'importConfirm'])->name('knowledge.import.confirm');
    Route::delete('/knowledge/{knowledge}', [KnowledgeAdminController::class, 'destroy'])->name('knowledge.destroy');
});

Route::resource('/knowledge', KnowledgeAdminController::class)
    ->except(['show', 'destroy'])
    ->middleware('can:content.view');
