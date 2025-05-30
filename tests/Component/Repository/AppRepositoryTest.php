<?php

namespace Tests\Component\Repository;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Support\Collection;
use Mockery;
use Nivseb\LaraMonitor\Repository\AppRepository;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

test(
    'getTransaction get value from app container',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.transaction'])
            ->andReturn($transaction);

        $repository = new AppRepository();
        expect($repository->getTransaction())->toBe($transaction);
    }
)
    ->with('all possible transaction types');

test(
    'getTransaction return null on fail',
    function (): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.transaction'])
            ->andThrow(new Exception());

        $repository = new AppRepository();
        expect($repository->getTransaction())->toBeNull();
    }
);

test(
    'getSpanList get value from app container',
    function (): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $spans = new Collection();

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.span.list'])
            ->andReturn($spans);

        $repository = new AppRepository();
        expect($repository->getSpanList())->toBe($spans);
    }
);

test(
    'getSpanList return null on fail',
    function (): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.span.list'])
            ->andThrow(new Exception());

        $repository = new AppRepository();
        expect($repository->getSpanList())->toBeNull();
    }
);

test(
    'getCurrentTraceEvent get value from app container',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $traceEvent = $buildTraceChild();

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.trace.event.current'])
            ->andReturn($traceEvent);

        $repository = new AppRepository();
        expect($repository->getCurrentTraceEvent())->toBe($traceEvent);
    }
)
    ->with('all possible child trace events');

test(
    'getCurrentTraceEvent return null on fail',
    function (): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.trace.event.current'])
            ->andThrow(new Exception());

        $repository = new AppRepository();
        expect($repository->getCurrentTraceEvent())->toBeNull();
    }
);

test(
    'getAllowedExitCode get value from app container',
    function (int $exitCode): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.exit.allowed'])
            ->andReturn($exitCode);

        $repository = new AppRepository();
        expect($repository->getAllowedExitCode())->toBe($exitCode);
    }
)
    ->with(
        [
            'command successful'   => [0],
            'command unsuccessful' => [1],
            'htpp successful'      => [200],
            'http unsuccessful'    => [500],
        ]
    );

test(
    'getAllowedExitCode return null on fail',
    function (): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.exit.allowed'])
            ->andThrow(new Exception());

        $repository = new AppRepository();
        expect($repository->getAllowedExitCode())->toBeNull();
    }
);

test(
    'resetData remove all values from app container',
    function (): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.transaction', null]);

        $containerMock->allows('instance')
            ->withArgs(['lara-monitor.trace.event.current', null]);

        $containerMock->allows('instance')
            ->withArgs(['lara-monitor.span.list', null]);

        $containerMock->allows('instance')
            ->withArgs(['lara-monitor.exit.allowed', null]);

        $repository = new AppRepository();
        expect($repository->resetData())->toBeTrue();
    }
);

test(
    'setTransaction store transaction in app container as transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.transaction', $transaction]);

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.trace.event.current');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.span.list');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.exit.allowed');

        $repository = new AppRepository();
        expect($repository->setTransaction($transaction, new Collection()))->toBeTrue();
    }
)
    ->with('all possible transaction types');

test(
    'setTransaction store transaction in app container as current trace event',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.transaction');

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.trace.event.current', $transaction]);

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.span.list');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.exit.allowed');

        $repository = new AppRepository();
        expect($repository->setTransaction($transaction, new Collection()))->toBeTrue();
    }
)
    ->with('all possible transaction types');

test(
    'setTransaction store given span list in app container',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();
        $spans       = new Collection();

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.transaction');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.trace.event.current');

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.span.list', $spans]);

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.exit.allowed');

        $repository = new AppRepository();
        expect($repository->setTransaction($transaction, $spans))->toBeTrue();
    }
)
    ->with('all possible transaction types');

test(
    'setTransaction reset allowed exit code',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();
        $spans       = new Collection();

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.transaction');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.trace.event.current');

        $containerMock->allows('instance')
            ->withArgs(['lara-monitor.span.list', $spans]);

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.exit.allowed', null]);

        $repository = new AppRepository();
        expect($repository->setTransaction($transaction, $spans))->toBeTrue();
    }
)
    ->with('all possible transaction types');

test(
    'setTransaction not successful on fail to set transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.transaction', $transaction])
            ->andThrow(new Exception());

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.trace.event.current');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.span.list');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.exit.allowed');

        $repository = new AppRepository();
        expect($repository->setTransaction($transaction, new Collection()))->toBeFalse();
    }
)
    ->with('all possible transaction types');

test(
    'setTransaction not successful on fail to set current trace event',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.transaction');

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.trace.event.current', $transaction])
            ->andThrow(new Exception());

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.span.list');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.exit.allowed');

        $repository = new AppRepository();
        expect($repository->setTransaction($transaction, new Collection()))->toBeFalse();
    }
)
    ->with('all possible transaction types');

test(
    'setTransaction not successful on fail to set current span list',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();
        $spans       = new Collection();

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.transaction');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.trace.event.current');

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.span.list', $spans])
            ->andThrow(new Exception());

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.exit.allowed');

        $repository = new AppRepository();
        expect($repository->setTransaction($transaction, $spans))->toBeFalse();
    }
)
    ->with('all possible transaction types');

test(
    'setTransaction not successful on fail to reseet allowed exit code',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();
        $spans       = new Collection();

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.transaction');

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.trace.event.current');

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.span.list', $spans]);

        $containerMock->allows('instance')
            ->withArgs(fn (...$args) => $args[0] === 'lara-monitor.exit.allowed')
            ->andThrow(new Exception());

        $repository = new AppRepository();
        expect($repository->setTransaction($transaction, $spans))->toBeFalse();
    }
)
    ->with('all possible transaction types');

test(
    'addSpan not add value to app container without span list',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $span = $buildSpan($buildTransaction());

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.span.list'])
            ->andThrow(new Exception());

        $repository = new AppRepository();
        expect($repository->addSpan($span))->toBeFalse();
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'addSpan with completed span is added to span list but not as current trace event',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        /** @var Collection<array-key, AbstractSpan>&Mockery\MockInterface $spanListMock */
        $spanListMock = Mockery::mock(Collection::class);

        $span = $buildSpan($buildTransaction(Carbon::now(), Carbon::now()));

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.span.list'])
            ->andReturn($spanListMock);

        $spanListMock->allows('add')
            ->once()
            ->withArgs([$span]);

        $repository = new AppRepository();
        expect($repository->addSpan($span))->toBeTrue();
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'addSpan with uncompleted span is added to span list but and set as current trace event',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        /** @var Collection<array-key, AbstractSpan>&Mockery\MockInterface $spanListMock */
        $spanListMock = Mockery::mock(Collection::class);

        $span           = $buildSpan($buildTransaction(Carbon::now(), Carbon::now()));
        $span->finishAt = null;

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.span.list'])
            ->andReturn($spanListMock);

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.trace.event.current', $span]);

        $spanListMock->allows('add')
            ->once()
            ->withArgs([$span]);

        $repository = new AppRepository();
        expect($repository->addSpan($span))->toBeTrue();
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'setCurrentTraceEvent set given child trace event as current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $traceEvent = $buildTraceChild();

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.trace.event.current', $traceEvent]);

        $repository = new AppRepository();
        expect($repository->setCurrentTraceEvent($traceEvent))->toBeTrue();
    }
)
    ->with('all possible child trace events');

test(
    'setCurrentTraceEvent set transaction on try to set non child trace event',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (AbstractTrace $traceEvent, Closure $buildTransaction): void {
        /** @var ContainerContract&Mockery\MockInterface $containerMock */
        $containerMock = Mockery::mock(ContainerContract::class);
        Container::setInstance($containerMock);

        $transaction = $buildTransaction();

        $containerMock->allows('get')
            ->once()
            ->withArgs(['lara-monitor.transaction'])
            ->andReturn($transaction);

        $containerMock->allows('instance')
            ->once()
            ->withArgs(['lara-monitor.trace.event.current', $transaction]);

        $repository = new AppRepository();
        expect($repository->setCurrentTraceEvent($traceEvent))->toBeFalse();
    }
)
    ->with('all trace events')
    ->with('all possible transaction types');
