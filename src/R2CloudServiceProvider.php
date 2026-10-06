<?php

namespace R2Cloud;

use Illuminate\Support\ServiceProvider;
use R2Cloud\Http\Middleware\R2EnsureValidAccountsToken;
use R2Cloud\Services\R2AccountsTokenService;

class R2CloudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/r2cloud.php', 'r2cloud');

        $this->app->singleton(
            R2AccountsTokenService::class,
            fn () => new R2AccountsTokenService()
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/r2cloud.php' => config_path('r2cloud.php'),
        ], 'config.r2cloud');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        $this->app['router']->aliasMiddleware(
            'r2cloud.token',
            R2EnsureValidAccountsToken::class
        );
    }
}