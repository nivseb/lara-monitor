<?php

namespace Nivseb\LaraMonitor\Providers;

use Carbon\Carbon;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Routing\Events\PreparingResponse;
use Illuminate\Routing\Events\ResponsePrepared;
use Illuminate\Support\Facades\Config;
use Nivseb\LaraMonitor\Collectors\SpanCollector;
use Nivseb\LaraMonitor\Facades\LaraMonitorApm;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Facades\LaraMonitorTransaction;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;

class LaraMonitorEndServiceProvider extends AbstractLaraMonitorServiceProvider
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
        if (Config::get('lara-monitor.feature.database.enabled')) {
            $this->registerDatabaseEvents($dispatcher);
        }
        if (Config::get('lara-monitor.feature.redis.enabled')) {
            $this->registerRedisEvents($dispatcher);
        }
        $this->registerSendTraceDataToApmServer();
    }

    public function registerSendTraceDataToApmServer(): void
    {
        $this->app->terminating(function (): void {
            $transaction = LaraMonitorTransaction::stopTransaction();
            if (!$transaction) {
                return;
            }
            LaraMonitorApm::finishCurrentTransaction();
            LaraMonitorStore::resetData();
        });
    }

    protected function registerBootEvents(Dispatcher $dispatcher): void
    {
        if ($this->isOctaneRunning()) {
            $dispatcher->listen(
                'Laravel\Octane\Events\RequestHandled',
                /**
                 * @param \Laravel\Octane\Events\RequestHandled $event
                 */
                function ($event): void {
                    LaraMonitorTransaction::stopMainAction($event);
                }
            );
        } else {
            $this->app->booted(
                function (): void {
                    LaraMonitorTransaction::booted();
                }
            );
        }
    }

    protected function registerRequestEvents(Dispatcher $dispatcher): void
    {
        if ($this->isOctaneRunning()) {
            return;
        }

        $dispatcher->listen(
            RequestHandled::class,
            function (RequestHandled $event): void {
                LaraMonitorTransaction::stopMainAction($event);
            }
        );
    }

    protected function registerScheduleEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(
            ScheduledTaskFinished::class,
            function (): void {
                LaraMonitorSpan::stopAction();
            }
        );

        $dispatcher->listen(
            ScheduledTaskFailed::class,
            function (): void {
                LaraMonitorSpan::stopAction();
            }
        );
    }

    protected function registerCommandEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(
            CommandFinished::class,
            function (CommandFinished $event): void {
                $transaction = LaraMonitorStore::getTransaction();
                if ($transaction instanceof CommandTransaction) {
                    LaraMonitorTransaction::stopMainAction($event);
                } else {
                    LaraMonitorSpan::stopAction();
                }
            }
        );
    }

    protected function registerJobEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(
            JobProcessed::class,
            function (JobProcessed $event): void {
                $transaction = LaraMonitorStore::getTransaction();
                if ($transaction instanceof JobTransaction) {
                    LaraMonitorTransaction::stopMainAction($event);
                    // send data
                    $transaction = LaraMonitorTransaction::stopTransaction();
                    if (!$transaction) {
                        return;
                    }
                    LaraMonitorApm::finishCurrentTransaction();
                    LaraMonitorStore::resetData();
                } else {
                    LaraMonitorSpan::stopAction();
                }
            }
        );
    }

    protected function registerDatabaseEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(QueryExecuted::class, function (QueryExecuted $event): void {
            /** @var null|SpanCollector $spanCollector */
            $spanCollector = Container::getInstance()->make(SpanCollector::class);
            $spanCollector?->trackDatabaseQuery($event, Carbon::now());
        });
    }

    protected function registerRedisEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(CommandExecuted::class, function (CommandExecuted $event): void {
            /** @var null|SpanCollector $spanCollector */
            $spanCollector = Container::getInstance()->make(SpanCollector::class);
            $spanCollector?->trackRedisCommand($event, Carbon::now());
        });
    }

    protected function registerPrepareResponseEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(ResponsePrepared::class, fn () => LaraMonitorSpan::stopAction());
    }

    protected function registerHttpClientEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(
            ResponseReceived::class,
            function (ResponseReceived $event): void {
                $span = LaraMonitorSpan::stopAction();
                if ($span instanceof HttpSpan) {
                    $span->responseCode = $event->response->status();
                }
            }
        );

        $dispatcher->listen(ConnectionFailed::class, fn () => LaraMonitorSpan::stopAction());
    }
}
