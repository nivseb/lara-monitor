<?php

namespace Tests\Component\Collectors;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Collectors\ErrorCollector;
use Nivseb\LaraMonitor\Contracts\AdditionalErrorDataContract;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Error;
use Throwable;

test(
    'captureError dont create error without current trace event',
    function (): void {
        /** @var MockInterface&RepositoryContract $storeMock */
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

        /** @var MockInterface&RepositoryContract $storeMock */
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

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureError($type, $code, $message, $handled, $time, null, $exception);
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

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureError($type, $code, $message, $handled, null, null, $exception);
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

        /** @var MockInterface&RepositoryContract $storeMock */
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

        /** @var MockInterface&RepositoryContract $storeMock */
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

        /** @var MockInterface&RepositoryContract $storeMock */
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

        /** @var MockInterface&RepositoryContract $storeMock */
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

test(
    'Build static message for ModelNotFoundException',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild, string $modelClass, mixed $ids, string $expectedMessage): void {
        $traceEvent = $buildTraceChild();
        $exception  = new ModelNotFoundException();
        $exception->setModel($modelClass, $ids);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureExceptionAsError($exception, fake()->boolean());

        expect($error->message)->toBe($expectedMessage);
    }
)
    ->with('all possible child trace events')
    ->with(
        [
            [
                'TestModel',
                1,
                'Instance for TestModel not found!',
            ],
            [
                'TestModel',
                'uuid1',
                'Instance for TestModel not found!',
            ],
            [
                'TestModel',
                [1, 2, 3],
                'Instance for TestModel not found!',
            ],
            [
                'TestModel',
                ['uuid1', 'uuid2', 'uuid3'],
                'Instance for TestModel not found!',
            ],
            [
                'App\Model\TestModel',
                1,
                'Instance for App\Model\TestModel not found!',
            ],
            [
                'App\Model\TestModel',
                'uuid1',
                'Instance for App\Model\TestModel not found!',
            ],
            [
                'App\Model\TestModel',
                [1, 2, 3],
                'Instance for App\Model\TestModel not found!',
            ],
            [
                'App\Model\TestModel',
                ['uuid1', 'uuid2', 'uuid3'],
                'Instance for App\Model\TestModel not found!',
            ],
        ]
    );

test(
    'Add ids from ModelNotFoundException as custom context',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild, mixed $ids, array $expectedData): void {
        $traceEvent = $buildTraceChild();
        $exception  = new ModelNotFoundException();
        $exception->setModel('TestModel', $ids);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureExceptionAsError($exception, fake()->boolean());

        expect($error->getCustomContext())->toBe($expectedData);
    }
)
    ->with('all possible child trace events')
    ->with(
        [
            [1, ['ids' => [1]]],
            ['uuid1', ['ids' => ['uuid1']]],
            [[1, 2, 3], ['ids' => [1, 2, 3]]],
            [['uuid1', 'uuid2', 'uuid3'], ['ids' => ['uuid1', 'uuid2', 'uuid3']]],
        ]
    );

test(
    'Add custom context from exception if the exception implements AdditionalErrorDataContract',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     * @param Closure() : Throwable               $buildException
     */
    function (Closure $buildTraceChild, Closure $buildException, array $expectedData): void {
        $traceEvent = $buildTraceChild();
        $exception  = $buildException();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $errorCollector = new ErrorCollector();
        $error          = $errorCollector->captureExceptionAsError($exception, fake()->boolean());

        expect($error->getCustomContext())->toBe($expectedData);
    }
)
    ->with('all possible child trace events')
    ->with(
        [
            [
                fn () => new class extends Exception implements AdditionalErrorDataContract {
                    public function getAdditionalErrorData(): ?array
                    {
                        return ['myValue1' => 1, 'myValue2' => 'text', 'myValue3' => true];
                    }
                },
                ['myValue1' => 1, 'myValue2' => 'text', 'myValue3' => true],
            ],
            [
                fn () => new class extends ModelNotFoundException implements AdditionalErrorDataContract {
                    public function getAdditionalErrorData(): ?array
                    {
                        return ['myValue1' => 1, 'myValue2' => 'text', 'myValue3' => true];
                    }
                },
                ['myValue1' => 1, 'myValue2' => 'text', 'myValue3' => true],
            ],
        ]
    );
