<?php

use App\Http\Middleware\ApiResponse;
use App\Http\Middleware\ConfigureFrontend;
use App\Http\Middleware\VerifyFrontendGateway;
use Dotenv\Dotenv;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use VistiqAdmin\Console\Commands\CreateSuperAdmin;
use VistiqAdmin\Console\Commands\ExpireAttempts;
use VistiqAdmin\Http\Middleware\EnsureAccountActive;
use VistiqAdmin\Http\Middleware\EnsureAdminAccess;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(using: function (): void {
        require __DIR__.'/../routes/web.php';
    }, commands: __DIR__.'/../routes/console.php')
    ->withCommands([ExpireAttempts::class, CreateSuperAdmin::class])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request): string => route('login'));
        $middleware->alias([
            'active' => EnsureAccountActive::class,
            'admin' => EnsureAdminAccess::class,
            'gateway' => VerifyFrontendGateway::class,
            'api.response' => ApiResponse::class,
        ]);
        $middleware->prepend(ConfigureFrontend::class);
    })
    ->withExceptions(fn (Exceptions $exceptions) => $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*') || $request->expectsJson()))
    ->create();

if (is_file($app->environmentPath().'/.env.local') && ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV')) !== 'testing') {
    Dotenv::createImmutable($app->environmentPath(), '.env')->safeLoad();
    Dotenv::createMutable($app->environmentPath(), '.env.local')->safeLoad();
}

return $app;
