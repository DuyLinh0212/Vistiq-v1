<?php

use App\Http\Controllers\FrontendProxyController;
use Illuminate\Support\Facades\Route;

Route::any('/{path?}', FrontendProxyController::class)->where('path', '.*');
