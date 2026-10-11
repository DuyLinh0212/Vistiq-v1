<?php

use Illuminate\Support\Facades\Route;
use VistiqAdmin\Http\Controllers\Admin\Learning\Knowledge\KnowledgeAdminController;

Route::resource('/knowledge', KnowledgeAdminController::class)->except(['show', 'destroy'])->middleware('can:content.view');
