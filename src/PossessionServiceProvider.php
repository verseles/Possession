<?php

namespace Verseles\Possession;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Verseles\Possession\Middleware\EnsureNotPossessing;
use Verseles\Possession\Middleware\ForbidDuringPossession;
use Verseles\Possession\Middleware\SharePossessionState;

class PossessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/possession.php', 'possession');

        $this->app->singleton(PossessionManager::class, function ($app) {
            return new PossessionManager;
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'possession');

        $this->registerMiddlewareAliases();
        $this->registerRoutes();
        $this->registerPublishables();
    }

    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('forbid-during-possession', ForbidDuringPossession::class);
        $router->aliasMiddleware('ensure-not-possessing', EnsureNotPossessing::class);
        $router->aliasMiddleware('share-possession-state', SharePossessionState::class);
    }

    protected function registerRoutes(): void
    {
        if (config('possession.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }

    protected function registerPublishables(): void
    {
        $this->publishes([
            __DIR__ . '/../config/possession.php' => config_path('possession.php'),
        ], 'possession-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/possession'),
        ], 'possession-views');

        $this->publishes([
            __DIR__ . '/../routes/web.php' => base_path('routes/possession.php'),
        ], 'possession-routes');
    }
}
