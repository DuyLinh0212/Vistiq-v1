<?php

use Dotenv\Dotenv;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(using: function (): void {
        require __DIR__.'/../routes/web.php';
    }, commands: __DIR__.'/../routes/console.php')
    ->withExceptions(fn (Exceptions $exceptions) => $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*') || $request->expectsJson()))
    ->create();

if (is_file($app->environmentPath().'/.env.local') && ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV')) !== 'testing') {
    Dotenv::createImmutable($app->environmentPath(), '.env')->safeLoad();
    Dotenv::createMutable($app->environmentPath(), '.env.local')->safeLoad();
}

return $app;
