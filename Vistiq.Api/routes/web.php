<?php

use App\Http\Controllers\SessionController;
use App\Services\FrontendContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/up', fn () => response()->json(['status' => 'ok', 'service' => 'Vistiq.Api']));
Route::get('/storage/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*');

Route::middleware(['gateway', 'web'])->group(__DIR__.'/web/'.app('vistiq.surface').'.php');

$webRoutes = array_values(Route::getRoutes()->getRoutes());
foreach ($webRoutes as $route) {
    if (! in_array('gateway', $route->middleware(), true)) {
        continue;
    }
    $action = $route->getAction();
    unset($action['prefix']);
    $action['middleware'] = ['web', 'api.response', ...array_values(array_diff($route->middleware(), ['gateway', 'web']))];
    $action['as'] = 'api.'.$route->getName();
    $uri = app(FrontendContext::class)->apiPath('/'.ltrim($route->uri(), '/'));
    Route::match($route->methods(), $uri, $action)->where($route->wheres);
}

$sessionPath = app('vistiq.surface') === 'admin' ? '/api/v1/admin/session' : '/api/v1/session';
Route::get($sessionPath, SessionController::class)->middleware('web')->name('api.session');
if (app('vistiq.surface') === 'learner') {
    Route::prefix('api')->middleware('api')->group(__DIR__.'/dictionary.php');
}
