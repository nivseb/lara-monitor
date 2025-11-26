<?php

namespace Tests\Component\Collectors\Transaction;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Laravel\Octane\Events\RequestHandled as OctaneRequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Collectors\Transaction\JobTransactionCollector;
use Nivseb\LaraMonitor\Contracts\Collector\SpanCollectorContract;
use Nivseb\LaraMonitor\Contracts\MapperContract;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\Tracing\ExternalTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Nivseb\LaraMonitor\Struct\User;
use ReflectionException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

test(
    'startTransaction create command transaction and store transaction',
    /**
     * @throws ReflectionException
     */
    function (): void {
        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storedTransaction = null;
        $storeMock->allows('setTransaction')
            ->once()
            ->withArgs(
                function (...$args) use (&$storedTransaction) {
                    if (!$args[0] instanceof JobTransaction) {
                        return false;
                    }
                    if (!$args[1] instanceof Collection || $args[1]->isNotEmpty()) {
                        return false;
                    }
                    $storedTransaction = $args[0];

                    return true;
                }
            )
            ->andReturn(true);

        $spanCollectorMock->allows('startAction')
            ->once()
            ->andReturn(
                new SystemSpan(
                    'dummy',
                    fake()->regexify('\w{10}'),
                    new JobTransaction(new StartTrace(false, 0.0)),
                    Carbon::now()->format('Uu'),
                )
            );

        $collector = new JobTransactionCollector();
        expect($collector->startTransaction())
            ->toBeInstanceOf(JobTransaction::class)
            ->toBe($storedTransaction);
    }
);

test(
    'startTransaction use correct data to create span',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $date = (new Carbon(fake()->dateTime()));
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('setTransaction')
            ->once()
            ->andReturn(true);

        $spanCollectorMock->allows('startAction')
            ->once()
            ->withArgs(
                fn (...$args) => $args[0] === 'booting'
                    && $args[1] === 'boot'
                    && $args[2] === null
                    && $date->eq($args[3])
                    && $args[4] === true
            )
            ->andReturn(
                new SystemSpan(
                    'dummy',
                    fake()->regexify('\w{10}'),
                    new JobTransaction(new StartTrace(false, 0.0)),
                    Carbon::now()->format('Uu'),
                )
            );

        $collector = new JobTransactionCollector();
        expect($collector->startTransaction())
            ->toBeInstanceOf(JobTransaction::class);
    }
);

test(
    'startTransactionFromRequest create command transaction and store transaction',
    /**
     * @throws ReflectionException
     */
    function (): void {
        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        /** @var MockInterface&SymfonyRequest $requestMock */
        $requestMock          = Mockery::mock(SymfonyRequest::class);
        $requestMock->headers = new HeaderBag([]);

        $storedTransaction = null;
        $storeMock->allows('setTransaction')
            ->once()
            ->withArgs(
                function (...$args) use (&$storedTransaction) {
                    if (!$args[0] instanceof JobTransaction) {
                        return false;
                    }
                    if (!$args[1] instanceof Collection || $args[1]->isNotEmpty()) {
                        return false;
                    }
                    $storedTransaction = $args[0];

                    return true;
                }
            )
            ->andReturn(true);

        $spanCollectorMock->allows('startAction')
            ->once()
            ->andReturn(
                new SystemSpan(
                    'dummy',
                    fake()->regexify('\w{10}'),
                    new JobTransaction(new StartTrace(false, 0.0)),
                    Carbon::now()->format('Uu'),
                )
            );

        $collector = new JobTransactionCollector();
        expect($collector->startTransactionFromRequest($requestMock))
            ->toBeInstanceOf(JobTransaction::class)
            ->toBe($storedTransaction);
    }
);

test(
    'startTransactionFromRequest use correct data to create span',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $date = (new Carbon(fake()->dateTime()));
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        /** @var MockInterface&SymfonyRequest $requestMock */
        $requestMock          = Mockery::mock(SymfonyRequest::class);
        $requestMock->headers = new HeaderBag([]);

        $storeMock->allows('setTransaction')
            ->once()
            ->andReturn(true);

        $spanCollectorMock->allows('startAction')
            ->once()
            ->withArgs(
                fn (...$args) => $args[0] === 'booting'
                    && $args[1] === 'boot'
                    && $args[2] === null
                    && $date->eq($args[3])
                    && $args[4] === true
            )
            ->andReturn(
                new SystemSpan(
                    'dummy',
                    fake()->regexify('\w{10}'),
                    new JobTransaction(new StartTrace(false, 0.0)),
                    Carbon::now()->format('Uu'),
                )
            );

        $collector = new JobTransactionCollector();
        expect($collector->startTransactionFromRequest($requestMock))
            ->toBeInstanceOf(JobTransaction::class);
    }
);

test(
    'startTransactionFromRequest get `traceparent` header from request',
    /**
     * @throws ReflectionException
     */
    function (W3CTraceParent $w3cTrace): void {
        Config::set('lara-monitor.ignoreExternalTrace', false);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        /** @var MockInterface&SymfonyRequest $requestMock */
        $requestMock          = Mockery::mock(SymfonyRequest::class);
        $requestMock->headers = new HeaderBag(['traceparent' => (string) $w3cTrace]);

        $storeMock->allows('setTransaction')
            ->once()
            ->andReturn(true);

        $spanCollectorMock->allows('startAction')
            ->once()
            ->andReturn(
                new SystemSpan(
                    'dummy',
                    fake()->regexify('\w{10}'),
                    new JobTransaction(new StartTrace(false, 0.0)),
                    Carbon::now()->format('Uu'),
                )
            );

        $collector   = new JobTransactionCollector();
        $transaction = $collector->startTransactionFromRequest($requestMock);

        /** @var ExternalTrace $trace */
        $trace = $transaction->getTrace();
        expect($trace)
            ->toBeInstanceOf(ExternalTrace::class)
            ->and($trace->w3cParent->version)->toBe($w3cTrace->version)
            ->and($trace->w3cParent->traceId)->toBe($w3cTrace->traceId)
            ->and($trace->w3cParent->parentId)->toBe($w3cTrace->parentId)
            ->and($trace->w3cParent->traceFlags)->toBe($w3cTrace->traceFlags);
    }
)
    ->with('w3c parents');

test(
    'startTransactionFromRequest ignore `traceparent` header from request if config is set to ignoring',
    /**
     * @throws ReflectionException
     */
    function (W3CTraceParent $w3cTrace): void {
        Config::set('lara-monitor.ignoreExternalTrace', true);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        /** @var MockInterface&SymfonyRequest $requestMock */
        $requestMock          = Mockery::mock(SymfonyRequest::class);
        $requestMock->headers = new HeaderBag(['traceparent' => (string) $w3cTrace]);

        $storeMock->allows('setTransaction')
            ->once()
            ->andReturn(true);

        $spanCollectorMock->allows('startAction')
            ->once()
            ->andReturn(
                new SystemSpan(
                    'dummy',
                    fake()->regexify('\w{10}'),
                    new JobTransaction(new StartTrace(false, 0.0)),
                    Carbon::now()->format('Uu'),
                )
            );

        $collector   = new JobTransactionCollector();
        $transaction = $collector->startTransactionFromRequest($requestMock);
        expect($transaction->getTrace())
            ->toBeInstanceOf(StartTrace::class);
    }
)
    ->with('w3c parents');

test(
    'startTransactionFromRequest start new trace if `traceparent` from request is broken',
    /**
     * @throws ReflectionException
     */
    function (string $invalidTraceParent): void {
        Config::set('lara-monitor.ignoreExternalTrace', false);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        /** @var MockInterface&SymfonyRequest $requestMock */
        $requestMock          = Mockery::mock(SymfonyRequest::class);
        $requestMock->headers = new HeaderBag(['traceparent' => $invalidTraceParent]);

        $storeMock->allows('setTransaction')
            ->once()
            ->andReturn(true);

        $spanCollectorMock->allows('startAction')
            ->once()
            ->andReturn(
                new SystemSpan(
                    'dummy',
                    fake()->regexify('\w{10}'),
                    new JobTransaction(new StartTrace(false, 0.0)),
                    Carbon::now()->format('Uu'),
                )
            );

        $collector   = new JobTransactionCollector();
        $transaction = $collector->startTransactionFromRequest($requestMock);
        expect($transaction->getTrace())
            ->toBeInstanceOf(StartTrace::class);
    }
)
    ->with('invalid trace header');

test(
    'booted not stop current action if no transaction exists',
    /**
     * @throws ReflectionException
     */
    function (): void {
        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturnNull();

        $spanCollectorMock->allows('stopAction')->never();

        $collector = new JobTransactionCollector();
        expect($collector->booted())->toBeNull();
    }
);

test(
    'booted stop current action if transaction exists',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $date        = (new Carbon(fake()->dateTime()));
        $transaction = new JobTransaction(new StartTrace(false, 0.00));
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $spanCollectorMock->allows('stopAction')->once()->withArgs(fn (...$args) => $date->eq($args[0]));

        $collector = new JobTransactionCollector();
        expect($collector->booted())->toBe($transaction);
    }
);

test(
    'stopTransaction does not fail if no transaction exists',
    /**
     * @throws ReflectionException
     */
    function (): void {
        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturnNull();

        $spanCollectorMock->allows('stopAction')->never();

        $collector = new JobTransactionCollector();
        expect($collector->stopTransaction())->toBeNull();
    }
);

test(
    'stopTransaction stop current transaction and action',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $date        = (new Carbon(fake()->dateTime()));
        $time        = $date->format('Uu');
        $transaction = new JobTransaction(new StartTrace(false, 0.00));
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $spanCollectorMock->allows('stopAction')->once()->withArgs(fn (...$args) => $date->eq($args[0]));

        $collector = new JobTransactionCollector();
        expect($collector->stopTransaction())
            ->toBe($transaction)
            ->and($transaction->finishAt)
            ->toEqual($time);
    }
);

test(
    'setUser does not fail if no transaction exists',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $guard = fake()->word();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        /** @var Authenticatable&MockInterface $userMock */
        $userMock = Mockery::mock(Authenticatable::class);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturnNull();

        $spanCollectorMock->allows('stopAction')->never();

        $collector = new JobTransactionCollector();
        $collector->setUser($guard, $userMock);
    }
);

test(
    'setUser set user to current transaction',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $guard       = fake()->word();
        $date        = (new Carbon(fake()->dateTime()));
        $transaction = new JobTransaction(new StartTrace(false, 0.00));
        Carbon::setTestNow($date);
        $user = new User();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        /** @var Authenticatable&MockInterface $userMock */
        $userMock = Mockery::mock(Authenticatable::class);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $mapperMock->allows('buildUserFromAuthenticated')->once()
            ->withArgs([$guard, $userMock])
            ->andReturn($user);

        $collector = new JobTransactionCollector();
        $collector->setUser($guard, $userMock);
        expect($transaction->getUser())
            ->toBe($user);
    }
);

test(
    'unsetUser does not fail if no transaction exists',
    /**
     * @throws ReflectionException
     */
    function (): void {
        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturnNull();

        $spanCollectorMock->allows('stopAction')->never();

        $collector = new JobTransactionCollector();
        $collector->unsetUser();
    }
);

test(
    'unsetUser set user to current transaction',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $date        = (new Carbon(fake()->dateTime()));
        $transaction = new JobTransaction(new StartTrace(false, 0.00));
        $transaction->setUser(new User());
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $collector = new JobTransactionCollector();
        $collector->unsetUser();
        expect($transaction->getUser())
            ->toBeNull();
    }
);

test(
    'startMainAction log error for wrong transaction with Command Starting Event',
    function (): void {
        $event = new CommandStarting('', new ArrayInput([]), new NullOutput());

        Log::shouldReceive('warning')
            ->withArgs(
                [
                    'Lara-Monitor: Can`t start main action for job transaction!',
                    ['error' => '`Nivseb\LaraMonitor\Collectors\Transaction\JobTransactionCollector` must called with `Illuminate\Queue\Events\JobProcessing` event, but called with `Illuminate\Console\Events\CommandStarting`!'],
                ]
            )
            ->once();

        $collector = new JobTransactionCollector();
        $collector->startMainAction($event);
    }
);

test(
    'startMainAction log error for wrong transaction with Request Received Event',
    function (): void {
        $event = new RequestReceived(App::getFacadeRoot(), App::getFacadeRoot(), new Request());

        Log::shouldReceive('warning')
            ->withArgs(
                [
                    'Lara-Monitor: Can`t start main action for job transaction!',
                    ['error' => '`Nivseb\LaraMonitor\Collectors\Transaction\JobTransactionCollector` must called with `Illuminate\Queue\Events\JobProcessing` event, but called with `Laravel\Octane\Events\RequestReceived`!'],
                ]
            )
            ->once();

        $collector = new JobTransactionCollector();
        $collector->startMainAction($event);
    }
);

test(
    'startMainAction log error for wrong transaction with Route Matched Event',
    function (): void {
        $event = new RouteMatched(new Route('', '', []), new Request());

        Log::shouldReceive('warning')
            ->withArgs(
                [
                    'Lara-Monitor: Can`t start main action for job transaction!',
                    ['error' => '`Nivseb\LaraMonitor\Collectors\Transaction\JobTransactionCollector` must called with `Illuminate\Queue\Events\JobProcessing` event, but called with `Illuminate\Routing\Events\RouteMatched`!'],
                ]
            )
            ->once();

        $collector = new JobTransactionCollector();
        $collector->startMainAction($event);
    }
);

test(
    'stopMainAction log error for wrong transaction with Command Finished Event',
    function (): void {
        $event = new CommandFinished('', new ArrayInput([]), new NullOutput(), 0);

        Log::shouldReceive('warning')
            ->withArgs(
                [
                    'Lara-Monitor: Can`t stop main action for job transaction!',
                    ['error' => '`Nivseb\LaraMonitor\Collectors\Transaction\JobTransactionCollector` must called with `Illuminate\Queue\Events\JobProcessed` event, but called with `Illuminate\Console\Events\CommandFinished`!'],
                ]
            )
            ->once();

        $collector = new JobTransactionCollector();
        $collector->stopMainAction($event);
    }
);

test(
    'stopMainAction log error for wrong transaction with Octane Request Handled Event',
    function (): void {
        $event = new OctaneRequestHandled(App::getFacadeRoot(), new Request(), new Response());

        Log::shouldReceive('warning')
            ->withArgs(
                [
                    'Lara-Monitor: Can`t stop main action for job transaction!',
                    ['error' => '`Nivseb\LaraMonitor\Collectors\Transaction\JobTransactionCollector` must called with `Illuminate\Queue\Events\JobProcessed` event, but called with `Laravel\Octane\Events\RequestHandled`!'],
                ]
            )
            ->once();

        $collector = new JobTransactionCollector();
        $collector->stopMainAction($event);
    }
);

test(
    'stopMainAction log error for wrong transaction with Laravel Request Handled Event',
    function (): void {
        $event = new RequestHandled(new Request(), new Response());

        Log::shouldReceive('warning')
            ->withArgs(
                [
                    'Lara-Monitor: Can`t stop main action for job transaction!',
                    ['error' => '`Nivseb\LaraMonitor\Collectors\Transaction\JobTransactionCollector` must called with `Illuminate\Queue\Events\JobProcessed` event, but called with `Illuminate\Foundation\Http\Events\RequestHandled`!'],
                ]
            )
            ->once();

        $collector = new JobTransactionCollector();
        $collector->stopMainAction($event);
    }
);

test(
    'stopMainAction starts main action for job and update transaction',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $jobName     = fake()->word();
        $date        = (new Carbon(fake()->dateTime()));
        $event       = new JobProcessing('', new SyncJob(App::getFacadeRoot(), '{"job":"'.$jobName.'"}', '', ''));
        $transaction = new JobTransaction(new StartTrace(false, 0.0));
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $spanCollectorMock->allows('startAction')->once()
            ->withArgs(
                fn (...$args) => $args[0] === 'run'
                    && $args[1] === 'app'
                    && $args[2] === 'handler'
                    && $date->eq($args[3])
                    && $args[4] === true
            )
            ->andReturn(new SystemSpan('dummy', fake()->regexify('\w{10}'), $transaction, Carbon::now()->format('Uu')));

        $collector = new JobTransactionCollector();
        $collector->startMainAction($event);

        expect($transaction->jobName)->toBe($jobName);
    }
);

test(
    'stopMainAction stop main action for successful job and update transaction',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $originalJobName = fake()->word();
        $date            = (new Carbon(fake()->dateTime()));
        $event           = new JobProcessed('', new SyncJob(App::getFacadeRoot(), '', '', ''));
        $transaction     = new JobTransaction(new StartTrace(false, 0.0));
        Carbon::setTestNow($date);
        $transaction->jobName = $originalJobName;

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $spanCollectorMock->allows('stopAction')->once()
            ->withArgs(fn (...$args) => $date->eq($args[0]))
            ->andReturn(new SystemSpan('dummy', fake()->regexify('\w{10}'), $transaction, Carbon::now()->format('Uu')));
        $spanCollectorMock->allows('startAction')->once()
            ->withArgs(
                fn (...$args) => $args[0] === 'terminating'
                    && $args[1] === 'terminate'
                    && $args[2] === null
                    && $date->eq($args[3])
                    && $args[4] === true
            )
            ->andReturn(new SystemSpan('dummy', fake()->regexify('\w{10}'), $transaction, Carbon::now()->format('Uu')));

        $collector = new JobTransactionCollector();
        $collector->stopMainAction($event);

        expect($transaction->failed)->toBeFalse()->and($transaction->jobName)->toBe($originalJobName);
    }
);

test(
    'stopMainAction stop main action for unsuccessful job and update transaction',
    /**
     * @throws ReflectionException
     */
    function (): void {
        $originalJobName = fake()->word();
        $date            = (new Carbon(fake()->dateTime()));
        $event           = new JobFailed('', new SyncJob(App::getFacadeRoot(), '', '', ''), new Exception());
        $transaction     = new JobTransaction(new StartTrace(false, 0.0));
        Carbon::setTestNow($date);
        $transaction->jobName = $originalJobName;

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $spanCollectorMock->allows('stopAction')->once()
            ->withArgs(fn (...$args) => $date->eq($args[0]))
            ->andReturn(new SystemSpan('dummy', fake()->regexify('\w{10}'), $transaction, Carbon::now()->format('Uu')));
        $spanCollectorMock->allows('startAction')->once()
            ->withArgs(
                fn (...$args) => $args[0] === 'terminating'
                    && $args[1] === 'terminate'
                    && $args[2] === null
                    && $date->eq($args[3])
                    && $args[4] === true
            )
            ->andReturn(new SystemSpan('dummy', fake()->regexify('\w{10}'), $transaction, Carbon::now()->format('Uu')));

        $collector = new JobTransactionCollector();
        $collector->stopMainAction($event);

        expect($transaction->failed)->toBeTrue()->and($transaction->jobName)->toBe($originalJobName);
    }
);
