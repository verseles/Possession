<?php

namespace Verseles\Possession;

use Illuminate\Support\ServiceProvider;

class PossessionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/possession.php', 'possession');
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'possession');

        $this->publishes([
            __DIR__.'/../config/possession.php' => config_path('possession.php'),
        ], 'possession-config');
    }
}