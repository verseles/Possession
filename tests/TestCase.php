<?php

namespace Verseles\Possession\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Verseles\Possession\PossessionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            PossessionServiceProvider::class,
        ];
    }
}
