<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Config::set('lara-monitor.enabled', true);
        Config::set('lara-monitor.elasticApm.enabled', true);
        Config::set('lara-monitor.elasticApm.baseUrl', 'http://mock/elastic-apm-server');
    }
}
