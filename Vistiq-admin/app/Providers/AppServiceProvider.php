<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config(['database.default' => 'backend', 'database.connections' => [], 'auth.guards' => [], 'auth.providers' => [], 'auth.passwords' => []]);
    }

    public function boot(): void {}
}
