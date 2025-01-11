<?php

namespace Nivseb\LaraMonitor\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

abstract class AbstractLaraMonitorServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        parent::register();

        if (!Config::get('lara-monitor.enabled')) {
            return;
        }

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->app->make('events');
        $this->registerBootEvents($dispatcher);
        $this->registerRequestEvents($dispatcher);
        $this->registerJobEvents($dispatcher);
        $this->registerScheduleEvents($dispatcher);
        $this->registerCommandEvents($dispatcher);
        $this->registerPrepareResponseEvents($dispatcher);
        if (Config::get('lara-monitor.feature.http.enabled')) {
            $this->registerHttpClientEvents($dispatcher);
        }
    }

    public function isQueueWorker(): bool
    {
        return $this->getCurrentCommand() === 'queue:work';
    }

    abstract protected function registerBootEvents(Dispatcher $dispatcher): void;

    abstract protected function registerRequestEvents(Dispatcher $dispatcher): void;

    abstract protected function registerScheduleEvents(Dispatcher $dispatcher): void;

    abstract protected function registerCommandEvents(Dispatcher $dispatcher): void;

    abstract protected function registerJobEvents(Dispatcher $dispatcher): void;

    abstract protected function registerHttpClientEvents(Dispatcher $dispatcher): void;

    abstract protected function registerPrepareResponseEvents(Dispatcher $dispatcher): void;

    protected function isOctaneRunning(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE']) && ((int)$_SERVER['LARAVEL_OCTANE'] === 1);
    }

    protected function getCurrentCommand(): ?string
    {
        return Arr::get($_SERVER, 'argv.1');
    }
}
