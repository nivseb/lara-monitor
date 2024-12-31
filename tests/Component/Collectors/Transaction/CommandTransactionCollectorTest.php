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
use Nivseb\LaraMonitor\Collectors\Transaction\CommandTransactionCollector;
use Nivseb\LaraMonitor\Contracts\Collector\SpanCollectorContract;
use Nivseb\LaraMonitor\Contracts\MapperContract;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Exceptions\WrongEventException;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\Tracing\ExternalTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\User;
use Laravel\Octane\Events\RequestHandled as OctaneRequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Throwable;

test(
    'startTransaction create command transaction and store transaction',
    function (): void {
        /** @var RepositoryContract&MockInterface $storeMock */
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
                    if (!$args[0] instanceof CommandTransaction) {
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
                    new CommandTransaction(new StartTrace(false, 0.0)),
                    Carbon::now(),
                )
            );

        $collector = new CommandTransactionCollector();
        expect($collector->startTransaction())
            ->toBeInstanceOf(CommandTransaction::class)
            ->toBe($storedTransaction);
    }
);

test(
    'startTransaction use correct data to create span',
    function (): void {
        $date = new Carbon(fake()->dateTime());
        Carbon::setTestNow($date);

        /** @var RepositoryContract&MockInterface $storeMock */
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
                    && $args[3] == $date
                    && $args[4] === true
            )
            ->andReturn(
                new SystemSpan(
                    'dummy',
                    fake()->regexify('\w{10}'),
                    new CommandTransaction(new StartTrace(false, 0.0)),
                    Carbon::now(),
                )
            );

        $collector = new CommandTransactionCollector();
        expect($collector->startTransaction())
            ->toBeInstanceOf(CommandTransaction::class);
    }
);

test(
    'startTransactionFromRequest create command transaction and store transaction',
    function (): void {
        /** @var RepositoryContract&MockInterface $storeMock */
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
                    if (!$args[0] instanceof CommandTransaction) {
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
                    new CommandTransaction(new StartTrace(false, 0.0)),
                    Carbon::now(),
                )
            );

        $collector = new CommandTransactionCollector();
        expect($collector->startTransactionFromRequest($requestMock))
            ->toBeInstanceOf(CommandTransaction::class)
            ->toBe($storedTransaction);
    }
);

test(
    'startTransactionFromRequest use correct data to create span',
    function (): void {
        $date = new Carbon(fake()->dateTime());
        Carbon::setTestNow($date);

        /** @var RepositoryContract&MockInterface $storeMock */
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
                    && $args[3] == $date
                    && $args[4] === true
            )
            ->andReturn(
                new SystemSpan(
                    'dummy',
                    fake()->regexify('\w{10}'),
                    new CommandTransaction(new StartTrace(false, 0.0)),
                    Carbon::now(),
                )
            );

        $collector = new CommandTransactionCollector();
        expect($collector->startTransactionFromRequest($requestMock))
            ->toBeInstanceOf(CommandTransaction::class);
    }
);

test(
    'startTransactionFromRequest get `traceparent` header from request',
    function (W3CTraceParent $w3cTrace): void {
        Config::set('lara-monitor.ignoreExternalTrace', false);

        /** @var RepositoryContract&MockInterface $storeMock */
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
                    new CommandTransaction(new StartTrace(false, 0.0)),
                    Carbon::now(),
                )
            );

        $collector   = new CommandTransactionCollector();
        $transaction = $collector->startTransactionFromRequest($requestMock);
        $trace       = $transaction->getTrace();
        expect($trace)
            ->toBeInstanceOf(ExternalTrace::class)
            /* @var ExternalTrace $trace */
            ->and($trace->w3cParent->version)->toBe($w3cTrace->version)
            ->and($trace->w3cParent->traceId)->toBe($w3cTrace->traceId)
            ->and($trace->w3cParent->parentId)->toBe($w3cTrace->parentId)
            ->and($trace->w3cParent->traceFlags)->toBe($w3cTrace->traceFlags);
    }
)
    ->with('w3c parents');

test(
    'startTransactionFromRequest ignore `traceparent` header from request if config is set to ignoring',
    function (W3CTraceParent $w3cTrace): void {
        Config::set('lara-monitor.ignoreExternalTrace', true);

        /** @var RepositoryContract&MockInterface $storeMock */
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
                    new CommandTransaction(new StartTrace(false, 0.0)),
                    Carbon::now(),
                )
            );

        $collector   = new CommandTransactionCollector();
        $transaction = $collector->startTransactionFromRequest($requestMock);
        expect($transaction->getTrace())
            ->toBeInstanceOf(StartTrace::class);
    }
)
    ->with('w3c parents');

test(
    'startTransactionFromRequest start new trace if `traceparent` from request is broken',
    function (string $invalidTraceParent): void {
        Config::set('lara-monitor.ignoreExternalTrace', false);

        /** @var RepositoryContract&MockInterface $storeMock */
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
                    new CommandTransaction(new StartTrace(false, 0.0)),
                    Carbon::now(),
                )
            );

        $collector   = new CommandTransactionCollector();
        $transaction = $collector->startTransactionFromRequest($requestMock);
        expect($transaction->getTrace())
            ->toBeInstanceOf(StartTrace::class);
    }
)
    ->with('invalid trace header');

test(
    'booted not stop current action if no transaction exists',
    function (): void {
        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturnNull();

        $spanCollectorMock->allows('stopAction')->never();

        $collector = new CommandTransactionCollector();
        expect($collector->booted())->toBeNull();
    }
);

test(
    'booted stop current action if transaction exists',
    function (): void {
        $date        = new Carbon(fake()->dateTime());
        $transaction = new CommandTransaction(new StartTrace(false, 0.00));
        Carbon::setTestNow($date);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $spanCollectorMock->allows('stopAction')->once()->withArgs(fn (...$args) => $date->eq($args[0]));

        $collector = new CommandTransactionCollector();
        expect($collector->booted())->toBe($transaction);
    }
);

test(
    'stopTransaction does not fail if no transaction exists',
    function (): void {
        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturnNull();

        $spanCollectorMock->allows('stopAction')->never();

        $collector = new CommandTransactionCollector();
        expect($collector->stopTransaction())->toBeNull();
    }
);

test(
    'stopTransaction stop current transaction and action',
    function (): void {
        $date        = new Carbon(fake()->dateTime());
        $transaction = new CommandTransaction(new StartTrace(false, 0.00));
        Carbon::setTestNow($date);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $spanCollectorMock->allows('stopAction')->once()->withArgs(fn (...$args) => $date->eq($args[0]));

        $collector = new CommandTransactionCollector();
        expect($collector->stopTransaction())
            ->toBe($transaction)
            ->and($transaction->finishAt)
            ->toEqual($date);
    }
);

test(
    'setUser does not fail if no transaction exists',
    function (): void {
        $guard = fake()->word();

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        /** @var Authenticatable&MockInterface $userMock */
        $userMock = Mockery::mock(Authenticatable::class);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturnNull();

        $spanCollectorMock->allows('stopAction')->never();

        $collector = new CommandTransactionCollector();
        $collector->setUser($guard, $userMock);
    }
);

test(
    'setUser set user to current transaction',
    function (): void {
        $guard       = fake()->word();
        $date        = new Carbon(fake()->dateTime());
        $transaction = new CommandTransaction(new StartTrace(false, 0.00));
        Carbon::setTestNow($date);
        $user = new User();

        /** @var RepositoryContract&MockInterface $storeMock */
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

        $collector = new CommandTransactionCollector();
        $collector->setUser($guard, $userMock);
        expect($transaction->getUser())
            ->toBe($user);
    }
);

test(
    'unsetUser does not fail if no transaction exists',
    function (): void {
        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturnNull();

        $spanCollectorMock->allows('stopAction')->never();

        $collector = new CommandTransactionCollector();
        $collector->unsetUser();
    }
);

test(
    'unsetUser set user to current transaction',
    function (): void {
        $date        = new Carbon(fake()->dateTime());
        $transaction = new CommandTransaction(new StartTrace(false, 0.00));
        $transaction->setUser(new User());
        Carbon::setTestNow($date);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $collector = new CommandTransactionCollector();
        $collector->unsetUser();
        expect($transaction->getUser())
            ->toBeNull();
    }
);

test(
    'startMainAction throw WrongEventException with Job Processing Event',
    function (): void {
        $event     = new JobProcessing('', new SyncJob(App::getFacadeRoot(), '', '', ''));
        $exception = null;

        try {
            $collector = new CommandTransactionCollector();
            $collector->startMainAction($event);
        } catch (Throwable $e) {
            $exception = $e;
        }
        expect($exception)
            ->toBeInstanceOf(WrongEventException::class);
    }
);

test(
    'startMainAction throw WrongEventException with Request Received Event',
    function (): void {
        $event     = new RequestReceived(App::getFacadeRoot(), App::getFacadeRoot(), new Request());
        $exception = null;

        try {
            $collector = new CommandTransactionCollector();
            $collector->startMainAction($event);
        } catch (Throwable $e) {
            $exception = $e;
        }
        expect($exception)
            ->toBeInstanceOf(WrongEventException::class);
    }
);

test(
    'startMainAction throw WrongEventException with Route Matched Event',
    function (): void {
        $event     = new RouteMatched(new Route('', '', []), new Request());
        $exception = null;

        try {
            $collector = new CommandTransactionCollector();
            $collector->startMainAction($event);
        } catch (Throwable $e) {
            $exception = $e;
        }
        expect($exception)
            ->toBeInstanceOf(WrongEventException::class);
    }
);

test(
    'stopMainAction throw WrongEventException with Job Failed Event',
    function (): void {
        $event     = new JobFailed('', new SyncJob(App::getFacadeRoot(), '', '', ''), new Exception());
        $exception = null;

        try {
            $collector = new CommandTransactionCollector();
            $collector->stopMainAction($event);
        } catch (Throwable $e) {
            $exception = $e;
        }
        expect($exception)
            ->toBeInstanceOf(WrongEventException::class);
    }
);

test(
    'stopMainAction throw WrongEventException with Job Processed Event',
    function (): void {
        $event     = new JobProcessed('', new SyncJob(App::getFacadeRoot(), '', '', ''));
        $exception = null;

        try {
            $collector = new CommandTransactionCollector();
            $collector->stopMainAction($event);
        } catch (Throwable $e) {
            $exception = $e;
        }
        expect($exception)
            ->toBeInstanceOf(WrongEventException::class);
    }
);

test(
    'stopMainAction throw WrongEventException with Octane Request Handled Event',
    function (): void {
        $event     = new OctaneRequestHandled(App::getFacadeRoot(), new Request(), new Response());
        $exception = null;

        try {
            $collector = new CommandTransactionCollector();
            $collector->stopMainAction($event);
        } catch (Throwable $e) {
            $exception = $e;
        }
        expect($exception)
            ->toBeInstanceOf(WrongEventException::class);
    }
);

test(
    'stopMainAction throw WrongEventException with Laravel Request Handled Event',
    function (): void {
        $event     = new RequestHandled(new Request(), new Response());
        $exception = null;

        try {
            $collector = new CommandTransactionCollector();
            $collector->stopMainAction($event);
        } catch (Throwable $e) {
            $exception = $e;
        }
        expect($exception)
            ->toBeInstanceOf(WrongEventException::class);
    }
);

test(
    'startMainAction starts main action for command and update transaction',
    /**
     * @throws WrongEventException
     */
    function (): void {
        $expectedCommand = fake()->word();
        $date            = new Carbon(fake()->dateTime());
        $event           = new CommandStarting($expectedCommand, new ArrayInput([]), new NullOutput());
        $transaction     = new CommandTransaction(new StartTrace(false, 0.00));
        Carbon::setTestNow($date);

        /** @var RepositoryContract&MockInterface $storeMock */
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
                    && $args[3] == $date
                    && $args[4] === true
            )
            ->andReturn(new SystemSpan('dummy', fake()->regexify('\w{10}'), $transaction, Carbon::now()));

        $collector = new CommandTransactionCollector();
        $collector->startMainAction($event);

        expect($transaction->command)->toBe($expectedCommand);
    }
);

test(
    'stopMainAction stop main action for command and update transaction',
    /**
     * @throws WrongEventException
     */
    function (): void {
        $originalCommand = fake()->word();
        $date            = new Carbon(fake()->dateTime());
        $exitCode        = fake()->numberBetween(0, 128);
        $event           = new CommandFinished('', new ArrayInput([]), new NullOutput(), $exitCode);
        $transaction     = new CommandTransaction(new StartTrace(false, 0.00));
        Carbon::setTestNow($date);
        $transaction->command = $originalCommand;

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MockInterface&SpanCollectorContract $spanCollectorMock */
        $spanCollectorMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $spanCollectorMock);

        $storeMock->allows('getTransaction')->once()->withNoArgs()->andReturn($transaction);

        $spanCollectorMock->allows('stopAction')->once()
            ->withArgs(fn (...$args) => $args[0] == $date)
            ->andReturn(new SystemSpan('dummy', fake()->regexify('\w{10}'), $transaction, Carbon::now()));
        $spanCollectorMock->allows('startAction')->once()
            ->withArgs(
                fn (...$args) => $args[0] === 'terminating'
                    && $args[1] === 'terminate'
                    && $args[2] === null
                    && $args[3] == $date
                    && $args[4] === true
            )
            ->andReturn(new SystemSpan('dummy', fake()->regexify('\w{10}'), $transaction, Carbon::now()));

        $collector = new CommandTransactionCollector();
        $collector->stopMainAction($event);

        expect($transaction->exitCode)->toBe($exitCode)->and($transaction->command)->toBe($originalCommand);
    }
);