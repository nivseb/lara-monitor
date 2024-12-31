<?php

namespace Tests;

use Nivseb\LaraMonitor\Providers\LaraMonitorEndServiceProvider;
use Nivseb\LaraMonitor\Providers\LaraMonitorStartServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class FeatureTestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LaraMonitorStartServiceProvider::class,
            LaraMonitorEndServiceProvider::class,
        ];
    }
}
