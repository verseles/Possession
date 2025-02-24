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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'possession');

        $this->publishes([
            __DIR__.'/../config/possession.php' => config_path('possession.php'),
        ], 'possession-config');

        // Load the helper file
        require_once __DIR__ . '/helpers.php';
    }
}
