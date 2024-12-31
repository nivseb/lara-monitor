<?php

namespace Tests\Component\Collectors;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Support\Facades\App;
use Nivseb\LaraMonitor\Collectors\ErrorCollector;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Error;
use Mockery;
use Mockery\MockInterface;

test(
    'captureError dont create error without current trace event',
    function (): void {
        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureError(fake()->word(), fake()->word(), fake()->text(100));
        expect($error)->toBeNull();
    }
);

test(
    'captureError create error for current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureError(fake()->word(), fake()->word(), fake()->text(100));
        expect($error)
            ->toBeInstanceOf(Error::class)
            ->and($error->parentEvent)
            ->toBe($traceEvent);
    }
)
    ->with('all possible child trace events');

test(
    'captureError map all given values correct',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $type       = fake()->word();
        $code       = fake()->word();
        $message    = fake()->text();
        $handled    = fake()->boolean();
        $time       = new Carbon(fake()->dateTime());
        $exception  = new Exception();

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureError($type, $code, $message, $handled, $time, $exception);
        expect($error)
            ->toBeInstanceOf(Error::class)
            ->and($error->type)->toBe($type)
            ->and($error->code)->toBe($code)
            ->and($error->message)->toBe($message)
            ->and($error->handled)->toBe($handled)
            ->and($error->time)->toEqual($time)
            ->and($error->throwable)->toBe($exception);
    }
)
    ->with('all possible child trace events');

test(
    'captureError use current time if time is not given',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $type       = fake()->word();
        $code       = fake()->word();
        $message    = fake()->text();
        $handled    = fake()->boolean();
        $time       = new Carbon(fake()->dateTime());
        $exception  = new Exception();
        Carbon::setTestNow($time);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureError($type, $code, $message, $handled, null, $exception);
        expect($error)
            ->toBeInstanceOf(Error::class)
            ->and($error->type)->toBe($type)
            ->and($error->code)->toBe($code)
            ->and($error->message)->toBe($message)
            ->and($error->handled)->toBe($handled)
            ->and($error->time)->toEqual($time)
            ->and($error->throwable)->toBe($exception);
    }
)
    ->with('all possible child trace events');

test(
    'captureExceptionAsError dont create error without current trace event',
    function (): void {
        $code      = fake()->numberBetween(1, 2048);
        $message   = fake()->text();
        $exception = new Exception($message, $code);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureExceptionAsError($exception);
        expect($error)->toBeNull();
    }
);

test(
    'captureExceptionAsError create error for current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $code       = fake()->numberBetween(1, 2048);
        $message    = fake()->text();
        $exception  = new Exception($message, $code);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureExceptionAsError($exception);
        expect($error)
            ->toBeInstanceOf(Error::class)
            ->and($error->parentEvent)
            ->toBe($traceEvent);
    }
)
    ->with('all possible child trace events');

test(
    'captureExceptionAsError map all given values correct',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $code       = fake()->numberBetween(1, 2048);
        $message    = fake()->text();
        $handled    = fake()->boolean();
        $time       = new Carbon(fake()->dateTime());
        $exception  = new Exception($message, $code);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureExceptionAsError($exception, $handled, $time);
        expect($error)
            ->toBeInstanceOf(Error::class)
            ->and($error->type)->toBe('Exception')
            ->and($error->code)->toBe($code)
            ->and($error->message)->toBe($message)
            ->and($error->handled)->toBe($handled)
            ->and($error->time)->toEqual($time)
            ->and($error->throwable)->toBe($exception);
    }
)
    ->with('all possible child trace events');

test(
    'captureExceptionAsError use current time if time is not given',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $code       = fake()->numberBetween(1, 2048);
        $message    = fake()->text();
        $handled    = fake()->boolean();
        $time       = (new Carbon(fake()->dateTime()));
        $exception  = new Exception($message, $code);
        Carbon::setTestNow($time);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureExceptionAsError($exception, $handled, null);
        expect($error)
            ->toBeInstanceOf(Error::class)
            ->and($error->type)->toBe('Exception')
            ->and($error->code)->toBe($code)
            ->and($error->message)->toBe($message)
            ->and($error->handled)->toBe($handled)
            ->and($error->time)->toEqual($time)
            ->and($error->throwable)->toBe($exception);
    }
)
    ->with('all possible child trace events');
