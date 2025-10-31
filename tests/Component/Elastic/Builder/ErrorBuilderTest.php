<?php

namespace Tests\Component\Elastic\Builder;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Exception;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Elastic\Builder\ErrorBuilder;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Error;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

test(
    'build no error records without defined errors',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'build single error record given from span has error wrapper',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        new Error(
            $span,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('error');
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'build single error record given from transaction has error wrapper',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        new Error(
            $transaction,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection());

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('error');
    }
)
    ->with('all possible transaction types');

test(
    'build error only with timestamp for error from span',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        new Error(
            $span,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->andReturnNull();

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'build error only with timestamp for error from transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        new Error(
            $transaction,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->andReturnNull();

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection());

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types');

test(
    'build all error records given from span',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        new Error(
            $span,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );
        new Error(
            $span,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(2)
            ->and($result[0])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('error')
            ->and($result[1])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('error');
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'build all error records given from transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());

        new Error(
            $transaction,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );
        new Error(
            $transaction,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection());

        expect($result)
            ->toBeArray()
            ->toHaveCount(2)
            ->and($result[0])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('error')
            ->and($result[1])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('error');
    }
)
    ->with('all possible transaction types');

test(
    'build all error records given from span and transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        new Error(
            $transaction,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );
        new Error(
            $span,
            fake()->word(),
            fake()->word(),
            (string) fake()->words(3, true),
            fake()->boolean(),
            new Carbon(fake()->dateTime())
        );

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(2)
            ->and($result[0])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('error')
            ->and($result[1])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('error');
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'map error data correct for span errors without throwable',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan, bool $isHandled): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        $type              = fake()->word();
        $code              = fake()->word();
        $message           = (string) fake()->words(3, true);
        $expectedErrorDate = new Carbon(fake()->dateTime());
        $timestamp         = fake()->numberBetween(10000);

        $error = new Error($span, $type, $code, $message, $isHandled, $expectedErrorDate);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$expectedErrorDate])->andReturn($timestamp);

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection([$span]));

        expect($result[0]['error'])
            ->toBeArray()
            ->toBe(
                [
                    'id'             => $error->id,
                    'transaction_id' => $transaction->getId(),
                    'parent_id'      => $span->getId(),
                    'trace_id'       => $transaction->getTraceId(),
                    'timestamp'      => $timestamp,
                    'culprit'        => null,
                    'exception'      => [
                        'message' => $message,
                        'type'    => $type,
                        'code'    => $code,
                        'handled' => $isHandled,
                    ],
                    'context' => null,
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types')
    ->with(
        [
            'handled'   => [true],
            'unhandled' => [false],
        ]
    );

test(
    'map error data correct for transaction errors without throwable',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, bool $isHandled): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());

        $type              = fake()->word();
        $code              = fake()->word();
        $message           = (string) fake()->words(3, true);
        $expectedErrorDate = new Carbon(fake()->dateTime());
        $timestamp         = fake()->numberBetween(10000);

        $error = new Error($transaction, $type, $code, $message, $isHandled, $expectedErrorDate);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$expectedErrorDate])->andReturn($timestamp);

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection());

        expect($result[0]['error'])
            ->toBeArray()
            ->toBe(
                [
                    'id'             => $error->id,
                    'transaction_id' => $transaction->getId(),
                    'parent_id'      => $transaction->getId(),
                    'trace_id'       => $transaction->getTraceId(),
                    'timestamp'      => $timestamp,
                    'culprit'        => null,
                    'exception'      => [
                        'message' => $message,
                        'type'    => $type,
                        'code'    => $code,
                        'handled' => $isHandled,
                    ],
                    'context' => null,
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'handled'   => [true],
            'unhandled' => [false],
        ]
    );

test(
    'map error data correct for span errors with throwable',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan, bool $isHandled): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        $type              = fake()->word();
        $code              = fake()->word();
        $message           = (string) fake()->words(3, true);
        $expectedErrorDate = new Carbon(fake()->dateTime());
        $timestamp         = fake()->numberBetween(10000);

        $throwable = new Exception();
        new Error($span, $type, $code, $message, $isHandled, $expectedErrorDate, throwable: $throwable);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$expectedErrorDate])->andReturn($timestamp);

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection([$span]));

        expect($result[0]['error'])
            ->toHaveKey('culprit')
            ->and($result[0]['error']['culprit'])
            ->toBe($throwable->getFile().':'.$throwable->getLine())
            ->and($result[0]['error']['exception'])
            ->toHaveKey('stacktrace')
            ->and($result[0]['error']['exception']['stacktrace'])
            ->toBeArray()
            ->toHaveCount(count($throwable->getTrace()));
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types')
    ->with(
        [
            'handled'   => [true],
            'unhandled' => [false],
        ]
    );

test(
    'map error data correct for transaction errors with throwable',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, bool $isHandled): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());

        $type              = fake()->word();
        $code              = fake()->word();
        $message           = (string) fake()->words(3, true);
        $expectedErrorDate = new Carbon(fake()->dateTime());
        $timestamp         = fake()->numberBetween(10000);

        $throwable = new Exception();
        new Error($transaction, $type, $code, $message, $isHandled, $expectedErrorDate, throwable: $throwable);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$expectedErrorDate])->andReturn($timestamp);

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection());

        expect($result[0]['error'])
            ->toHaveKey('culprit')
            ->and($result[0]['error']['culprit'])
            ->toBe($throwable->getFile().':'.$throwable->getLine())
            ->and($result[0]['error']['exception'])
            ->toHaveKey('stacktrace')
            ->and($result[0]['error']['exception']['stacktrace'])
            ->toBeArray()
            ->toHaveCount(count($throwable->getTrace()));
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'handled'   => [true],
            'unhandled' => [false],
        ]
    );

test(
    'map custom data correct for span errors',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan, bool $isHandled): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        $type              = fake()->word();
        $code              = fake()->word();
        $message           = (string) fake()->words(3, true);
        $expectedErrorDate = new Carbon(fake()->dateTime());
        $timestamp         = fake()->numberBetween(10000);

        $error = new Error($span, $type, $code, $message, $isHandled, $expectedErrorDate);
        $error->setCustomContext('myValue1', 1);
        $error->setCustomContext('myValue2', 'text');
        $error->setCustomContext('myValue3', true);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$expectedErrorDate])->andReturn($timestamp);

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection([$span]));

        expect($result[0]['error'])
            ->toHaveKey('context')
            ->and($result[0]['error']['context'])
            ->toBe(['custom' => ['myValue1' => 1, 'myValue2' => 'text', 'myValue3' => true]]);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types')
    ->with(
        [
            'handled'   => [true],
            'unhandled' => [false],
        ]
    );

test(
    'map custom data correct for transaction errors',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, bool $isHandled): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());

        $type              = fake()->word();
        $code              = fake()->word();
        $message           = (string) fake()->words(3, true);
        $expectedErrorDate = new Carbon(fake()->dateTime());
        $timestamp         = fake()->numberBetween(10000);

        $error = new Error($transaction, $type, $code, $message, $isHandled, $expectedErrorDate);
        $error->setCustomContext('myValue1', 1);
        $error->setCustomContext('myValue2', 'text');
        $error->setCustomContext('myValue3', true);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$expectedErrorDate])->andReturn($timestamp);

        $errorBuilder = new ErrorBuilder($formaterMock);
        $result       = $errorBuilder->buildErrorRecords($transaction, new Collection());

        expect($result[0]['error'])
            ->toHaveKey('context')
            ->and($result[0]['error']['context'])
            ->toBe(['custom' => ['myValue1' => 1, 'myValue2' => 'text', 'myValue3' => true]]);
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'handled'   => [true],
            'unhandled' => [false],
        ]
    );
