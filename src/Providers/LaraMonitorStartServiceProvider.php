<?php

namespace Nivseb\LaraMonitor\Providers;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Queue\Events\JobPopped;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Routing\Events\PreparingResponse;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Octane\Events\RequestReceived;
use Nivseb\LaraMonitor\Collectors\ErrorCollector;
use Nivseb\LaraMonitor\Collectors\SpanCollector;
use Nivseb\LaraMonitor\Collectors\Transaction\CommandTransactionCollector;
use Nivseb\LaraMonitor\Collectors\Transaction\JobTransactionCollector;
use Nivseb\LaraMonitor\Collectors\Transaction\OctaneRequestTransactionCollector;
use Nivseb\LaraMonitor\Collectors\Transaction\RequestTransactionCollector;
use Nivseb\LaraMonitor\Contracts\AnalyserContract;
use Nivseb\LaraMonitor\Contracts\ApmAgentContract;
use Nivseb\LaraMonitor\Contracts\ApmServiceContract;
use Nivseb\LaraMonitor\Contracts\Collector\ErrorCollectorContract;
use Nivseb\LaraMonitor\Contracts\Collector\SpanCollectorContract;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\CommandCollectorContract;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\JobCollectorContract;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\RequestCollectorContract;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\TransactionCollectorContract;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Contracts\Elastic\ErrorBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\MetaBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\MetricBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\SpanBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\TransactionBuilderContract;
use Nivseb\LaraMonitor\Contracts\MapperContract;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Elastic\Builder\ErrorBuilder;
use Nivseb\LaraMonitor\Elastic\Builder\MetaBuilder;
use Nivseb\LaraMonitor\Elastic\Builder\MetricBuilder;
use Nivseb\LaraMonitor\Elastic\Builder\SpanBuilder;
use Nivseb\LaraMonitor\Elastic\Builder\TransactionBuilder;
use Nivseb\LaraMonitor\Elastic\ElasticAgent;
use Nivseb\LaraMonitor\Elastic\ElasticFormater;
use Nivseb\LaraMonitor\Facades\LaraMonitorError;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Facades\LaraMonitorTransaction;
use Nivseb\LaraMonitor\Http\CollectingMiddleware;
use Nivseb\LaraMonitor\Http\TraceParentMiddleware;
use Nivseb\LaraMonitor\Repository\AppRepository;
use Nivseb\LaraMonitor\Services\Analyser;
use Nivseb\LaraMonitor\Services\ApmService;
use Nivseb\LaraMonitor\Services\Mapper;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Throwable;

class LaraMonitorStartServiceProvider extends AbstractLaraMonitorServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/lara-monitor.php', 'lara-monitor');
        parent::register();

        $this->registerDefaultServices();
        $this->registerDefaultCollectors();
        if (!Config::get('lara-monitor.enabled')) {
            return;
        }
        $this->registerElasticAgent();
        $this->registerExceptionReporting();

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->app->make('events');
        $this->registerAuthReporting($dispatcher);
        $this->registerTransactionCollector();
    }

    public function boot(): void
    {
        $this->publishes(
            [__DIR__.'/../../config/lara-monitor.php' => $this->app->configPath('lara-monitor.php')],
            'lara-monitor-config'
        );
    }

    protected function registerDefaultServices(): void
    {
        $this->app->scoped(RepositoryContract::class, AppRepository::class);
        $this->app->scoped(ApmServiceContract::class, ApmService::class);
        $this->app->scoped(MapperContract::class, Mapper::class);
        $this->app->scoped(AnalyserContract::class, Analyser::class);
    }

    protected function registerElasticAgent(): void
    {
        if (!Config::get('lara-monitor.elasticApm.enabled')) {
            return;
        }
        $this->app->scoped(ElasticFormaterContract::class, ElasticFormater::class);
        $this->app->scoped(TransactionBuilderContract::class, TransactionBuilder::class);
        $this->app->scoped(SpanBuilderContract::class, SpanBuilder::class);
        $this->app->scoped(ErrorBuilderContract::class, ErrorBuilder::class);
        $this->app->scoped(MetaBuilderContract::class, MetaBuilder::class);
        $this->app->scoped(MetricBuilderContract::class, MetricBuilder::class);
        $this->app->scoped(ApmAgentContract::class, ElasticAgent::class);
    }

    protected function registerDefaultCollectors(): void
    {
        if ($this->isOctaneRunning()) {
            $this->app->bind(RequestCollectorContract::class, OctaneRequestTransactionCollector::class);
        } else {
            $this->app->bind(RequestCollectorContract::class, RequestTransactionCollector::class);
        }
        $this->app->bind(CommandCollectorContract::class, CommandTransactionCollector::class);
        $this->app->bind(JobCollectorContract::class, JobTransactionCollector::class);
        $this->app->bind(SpanCollectorContract::class, SpanCollector::class);
        $this->app->bind(ErrorCollectorContract::class, ErrorCollector::class);
    }

    protected function registerTransactionCollector(): void
    {
        $this->app->scoped(
            TransactionCollectorContract::class,
            function (Application $app) {
                if (!$app->runningInConsole()) {
                    return $app->make(RequestCollectorContract::class);
                }
                if ($this->isQueueWorker()) {
                    return $app->make(JobCollectorContract::class);
                }

                return $app->make(CommandCollectorContract::class);
            }
        );
    }

    /**
     * @throws BindingResolutionException
     */
    protected function registerExceptionReporting(): void
    {
        /** @var Handler $handler */
        $handler = $this->app->make(ExceptionHandler::class);
        $handler->reportable(function (Throwable $exception): bool {
            LaraMonitorError::captureExceptionAsError($exception);

            return true;
        });
    }

    protected function registerAuthReporting(Dispatcher $dispatcher): void
    {
        if (!Config::get('lara-monitor.feature.auth.enabled')) {
            return;
        }

        $dispatcher->listen(
            Authenticated::class,
            function (Authenticated $event): void {
                LaraMonitorTransaction::setUser($event->guard, $event->user);
            }
        );
    }

    protected function registerBootEvents(Dispatcher $dispatcher): void
    {
        if ($this->isOctaneRunning()) {
            $dispatcher->listen(
                'Laravel\Octane\Events\RequestReceived',
                /**
                 * @param RequestReceived $event
                 */
                function ($event): void {
                    LaraMonitorTransaction::startTransactionFromRequest($event->request);
                    LaraMonitorTransaction::booted();
                    LaraMonitorTransaction::startMainAction($event);
                }
            );
        } else {
            $this->app->booting(
                function (): void {
                    LaraMonitorTransaction::startTransactionFromRequest(
                        Container::getInstance()->make('request')
                    );
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
            RouteMatched::class,
            function (RouteMatched $event): void {
                LaraMonitorTransaction::startMainAction($event);
            }
        );
    }

    protected function registerScheduleEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(
            ScheduledTaskStarting::class,
            function (ScheduledTaskStarting $event): void {
                $name = $event->task instanceof CallbackEvent ?
                    $event->task->getSummaryForDisplay() :
                    trim(str_replace(ConsoleApplication::phpBinary(), '', $event->task->command ?? ''));
                $type = $event->task->runInBackground ? 'start' : 'call';
                LaraMonitorSpan::startAction($type.' '.$name, 'schedule', 'run', system: true);
            }
        );
    }

    protected function registerCommandEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(
            CommandStarting::class,
            function (CommandStarting $event): void {
                $transaction = LaraMonitorStore::getTransaction();
                if ($transaction instanceof CommandTransaction && $event->command === $this->getCurrentCommand()) {
                    LaraMonitorTransaction::startMainAction($event);
                } else {
                    LaraMonitorSpan::startAction('call '.$event->command, 'command', 'call', system: true);
                }
            }
        );
    }

    protected function registerJobEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(
            JobQueued::class,
            function (JobQueued $event): void {
                LaraMonitorSpan::startAction('queue Job '.$event->id, 'queue', 'dispatch');
                LaraMonitorSpan::stopAction();
            }
        );

        $dispatcher->listen(
            JobPopped::class,
            function (): void {
                LaraMonitorTransaction::startTransaction(Container::getInstance(), null);
            }
        );

        $dispatcher->listen(
            JobProcessing::class,
            function (JobProcessing $event): void {
                $transaction = LaraMonitorStore::getTransaction();
                if (!$transaction) {
                    $transaction = LaraMonitorTransaction::startTransaction(Container::getInstance(), null);
                }
                if ($transaction instanceof JobTransaction) {
                    LaraMonitorTransaction::booted();
                    LaraMonitorTransaction::startMainAction($event);
                } else {
                    LaraMonitorSpan::startAction('run '.$event->job->getName(), 'queue', 'work', system: true);
                }
            }
        );
    }

    protected function registerPrepareResponseEvents(Dispatcher $dispatcher): void
    {
        $dispatcher->listen(PreparingResponse::class, function (PreparingResponse $event): void {
            LaraMonitorSpan::startRenderAction($event->response);
        });
    }

    protected function registerHttpClientEvents(Dispatcher $dispatcher): void
    {
        if (Config::get('lara-monitor.feature.http.middleware.traceParent')) {
            Http::globalMiddleware(new TraceParentMiddleware());
        }

        if (Config::get('lara-monitor.feature.http.collecting.middleware')) {
            Http::globalMiddleware(new CollectingMiddleware());
        }

        if (Config::get('lara-monitor.feature.http.collecting.events')) {
            $dispatcher->listen(
                RequestSending::class,
                function (RequestSending $event): void {
                    LaraMonitorSpan::startHttpAction($event->request->toPsrRequest());
                }
            );
        }
    }
}
