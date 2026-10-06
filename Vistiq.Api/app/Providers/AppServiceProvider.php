<?php

namespace App\Providers;

use App\Routing\ApiControllerDispatcher;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Routing\Contracts\ControllerDispatcher;
use Illuminate\Support\ServiceProvider;
use VistiqAdmin\Jobs\ProcessAiTask;
use VistiqCore\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ControllerDispatcher::class, ApiControllerDispatcher::class);
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
        $surface = str_starts_with($path, '/api/')
            ? (preg_match('#^/api/v1/admin(?:/|$)#', $path) ? 'admin' : 'learner')
            : ($_SERVER['HTTP_X_VISTIQ_SURFACE'] ?? config('frontends.default_surface'));
        $surface = $surface === 'admin' ? 'admin' : 'learner';
        $_SERVER['APP_ROUTES_CACHE'] = 'bootstrap/cache/routes-'.$surface.'.php';
        $this->app->instance('vistiq.surface', $surface);
        $frontend = config('frontends.'.$surface.'.path');
        if (! $this->app->runningInConsole() || $this->app->runningUnitTests()) {
            $this->app->usePublicPath($frontend.'/public');
        }
        config([
            'auth.providers.users.model' => $surface === 'admin' ? \VistiqAdmin\Models\User::class : \VistiqLearner\Models\User::class,
            'session.cookie' => 'vistiq_'.$surface.'_session',
            'view.paths' => [$frontend.'/resources/views', config('frontends.admin.path').'/resources/views'],
        ]);
        $this->app->register($surface === 'admin' ? \VistiqAdmin\Providers\AppServiceProvider::class : \VistiqLearner\Providers\AppServiceProvider::class);
    }

    public function boot(): void
    {
        if (! class_exists('App\\Jobs\\ProcessAiTask', false)) {
            class_alias(ProcessAiTask::class, 'App\\Jobs\\ProcessAiTask');
        }
        Factory::guessFactoryNamesUsing(function (string $model): string {
            $surface = str_starts_with($model, 'VistiqLearner\\') ? 'Learner' : 'Admin';

            return 'Database\\'.$surface.'\\Factories\\'.class_basename($model).'Factory';
        });
        ResetPassword::createUrlUsing(fn (User $user, string $token): string => rtrim(config('frontends.'.app('vistiq.surface').'.url'), '/').'/reset-password/'.$token.'?'.http_build_query(['email' => $user->email]));
    }
}
