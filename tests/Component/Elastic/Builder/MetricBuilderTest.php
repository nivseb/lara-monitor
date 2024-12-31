<?php

namespace Tests\Component\Elastic\Builder;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Elastic\Builder\MetricBuilder;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Mockery;
use Mockery\MockInterface;

test(
    'build successful metric set',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan, float $transactionDuration, float $spanDuration, int $expectedAppSum, int $expectedSpanSum): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transactionType       = fake()->word();
        $spanType              = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);
        $span                  = $buildSpan($transaction);
        $span->startAt         = new Carbon(fake()->dateTime());
        $span->finishAt        = new Carbon(fake()->dateTime());

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTransactionType')
            ->once()
            ->withArgs([$transaction])
            ->andReturn($transactionType);
        $formaterMock
            ->allows('getTimestamp')
            ->once()
            ->withArgs([$transactionStartAt])
            ->andReturn($timestamp);
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$transactionStartAt, $transactionFinishedAt])
            ->andReturn($transactionDuration);
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$span->startAt, $span->finishAt])
            ->andReturn($spanDuration);
        $formaterMock
            ->allows('getSpanTypeData')
            ->once()
            ->withArgs([$span])
            ->andReturn(new TypeData($spanType));

        $transactionBuilder = new MetricBuilder($formaterMock);
        $result             = $transactionBuilder->buildSpanMetrics($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toBe(
                [
                    [
                        'metricset' => [
                            'samples' => [
                                'transaction.breakdown.count'  => ['value' => 1],
                                'transaction.duration.sum.us'  => ['value' => 1],
                                'transaction.self_time.sum.us' => ['value' => 1],
                                'span.self_time.count'         => ['value' => 1],
                                'span.self_time.sum.us'        => ['value' => $expectedSpanSum],
                            ],
                            'timestamp'   => $timestamp,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => $spanType, 'subtype' => ''],
                        ],
                    ],
                    [
                        'metricset' => [
                            'samples' => [
                                'transaction.breakdown.count'  => ['value' => 1],
                                'transaction.duration.sum.us'  => ['value' => 1],
                                'transaction.self_time.sum.us' => ['value' => 1],
                                'span.self_time.count'         => ['value' => 1],
                                'span.self_time.sum.us'        => ['value' => $expectedAppSum],
                            ],
                            'timestamp'   => $timestamp,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => 'app', 'subtype' => ''],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all non system span types')
    ->with(
        [
            '50%/50%'                   => [1000, 500, 500, 500],
            'float need to cast to int' => [1000.5, 500.5, 500, 500],
            'diff round for floats'     => [1000, 500.5, 499, 500],
        ]
    );

test(
    'dont build metric sets without transaction duration',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);
        $span                  = $buildSpan($transaction);
        $span->startAt         = new Carbon(fake()->dateTime());
        $span->finishAt        = new Carbon(fake()->dateTime());

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTimestamp')
            ->once()
            ->withArgs([$transactionStartAt])
            ->andReturn($timestamp);
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$transactionStartAt, $transactionFinishedAt])
            ->andReturnNull();

        $transactionBuilder = new MetricBuilder($formaterMock);
        $result             = $transactionBuilder->buildSpanMetrics($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all non system span types');

test(
    'dont build metric sets without transaction timestamp',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);
        $span                  = $buildSpan($transaction);
        $span->startAt         = new Carbon(fake()->dateTime());
        $span->finishAt        = new Carbon(fake()->dateTime());

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTimestamp')
            ->once()
            ->withArgs([$transactionStartAt])
            ->andReturnNull();
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$transactionStartAt, $transactionFinishedAt])
            ->andReturn(fake()->randomFloat());

        $transactionBuilder = new MetricBuilder($formaterMock);
        $result             = $transactionBuilder->buildSpanMetrics($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all non system span types');

test(
    'ignore spans without durations for metrics',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan, float $transactionDuration, int $expectedAppSum): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transactionType       = fake()->word();
        $spanType              = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);
        $span                  = $buildSpan($transaction);
        $span->startAt         = new Carbon(fake()->dateTime());
        $span->finishAt        = new Carbon(fake()->dateTime());

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTransactionType')
            ->once()
            ->withArgs([$transaction])
            ->andReturn($transactionType);
        $formaterMock
            ->allows('getTimestamp')
            ->once()
            ->withArgs([$transactionStartAt])
            ->andReturn($timestamp);
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$transactionStartAt, $transactionFinishedAt])
            ->andReturn($transactionDuration);
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$span->startAt, $span->finishAt])
            ->andReturnNull();
        $formaterMock
            ->allows('getSpanTypeData')
            ->once()
            ->withArgs([$span])
            ->andReturn(new TypeData($spanType));

        $transactionBuilder = new MetricBuilder($formaterMock);
        $result             = $transactionBuilder->buildSpanMetrics($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toBe(
                [
                    [
                        'metricset' => [
                            'samples' => [
                                'transaction.breakdown.count'  => ['value' => 1],
                                'transaction.duration.sum.us'  => ['value' => 1],
                                'transaction.self_time.sum.us' => ['value' => 1],
                                'span.self_time.count'         => ['value' => 1],
                                'span.self_time.sum.us'        => ['value' => $expectedAppSum],
                            ],
                            'timestamp'   => $timestamp,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => 'app', 'subtype' => ''],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all non system span types')
    ->with(
        [
            'transaction duration is int'   => [1000, 1000],
            'transaction duration is float' => [1000.5, 1000],
        ]
    );

test(
    'ignore spans without type data for metrics',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan, float $transactionDuration, int $expectedAppSum): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transactionType       = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);
        $span                  = $buildSpan($transaction);
        $span->startAt         = new Carbon(fake()->dateTime());
        $span->finishAt        = new Carbon(fake()->dateTime());

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTransactionType')
            ->once()
            ->withArgs([$transaction])
            ->andReturn($transactionType);
        $formaterMock
            ->allows('getTimestamp')
            ->once()
            ->withArgs([$transactionStartAt])
            ->andReturn($timestamp);
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$transactionStartAt, $transactionFinishedAt])
            ->andReturn($transactionDuration);
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$span->startAt, $span->finishAt])
            ->andReturn(fake()->randomFloat(2, 1));
        $formaterMock
            ->allows('getSpanTypeData')
            ->once()
            ->withArgs([$span])
            ->andReturnNull();

        $transactionBuilder = new MetricBuilder($formaterMock);
        $result             = $transactionBuilder->buildSpanMetrics($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toBe(
                [
                    [
                        'metricset' => [
                            'samples' => [
                                'transaction.breakdown.count'  => ['value' => 1],
                                'transaction.duration.sum.us'  => ['value' => 1],
                                'transaction.self_time.sum.us' => ['value' => 1],
                                'span.self_time.count'         => ['value' => 1],
                                'span.self_time.sum.us'        => ['value' => $expectedAppSum],
                            ],
                            'timestamp'   => $timestamp,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => 'app', 'subtype' => ''],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all non system span types')
    ->with(
        [
            'transaction duration is int'   => [1000, 1000],
            'transaction duration is float' => [1000.5, 1000],
        ]
    );

test(
    'build app metric without spans',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, float $transactionDuration, int $expectedAppSum): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transactionType       = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTransactionType')
            ->once()
            ->withArgs([$transaction])
            ->andReturn($transactionType);
        $formaterMock
            ->allows('getTimestamp')
            ->once()
            ->withArgs([$transactionStartAt])
            ->andReturn($timestamp);
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$transactionStartAt, $transactionFinishedAt])
            ->andReturn($transactionDuration);

        $transactionBuilder = new MetricBuilder($formaterMock);
        $result             = $transactionBuilder->buildSpanMetrics($transaction, new Collection());

        expect($result)
            ->toBeArray()
            ->toBe(
                [
                    [
                        'metricset' => [
                            'samples' => [
                                'transaction.breakdown.count'  => ['value' => 1],
                                'transaction.duration.sum.us'  => ['value' => 1],
                                'transaction.self_time.sum.us' => ['value' => 1],
                                'span.self_time.count'         => ['value' => 1],
                                'span.self_time.sum.us'        => ['value' => $expectedAppSum],
                            ],
                            'timestamp'   => $timestamp,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => 'app', 'subtype' => ''],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'transaction duration is int'   => [1000, 1000],
            'transaction duration is float' => [1000.5, 1000],
        ]
    );

test(
    'ignore system spans',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, float $transactionDuration, int $expectedAppSum): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transactionType       = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        $span = new SystemSpan(
            'test',
            'test',
            $transaction,
            $transactionStartAt->clone(),
            $transactionFinishedAt->clone()
        );

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTransactionType')
            ->once()
            ->withArgs([$transaction])
            ->andReturn($transactionType);
        $formaterMock
            ->allows('getTimestamp')
            ->once()
            ->withArgs([$transactionStartAt])
            ->andReturn($timestamp);
        $formaterMock
            ->allows('calcDuration')
            ->once()
            ->withArgs([$transactionStartAt, $transactionFinishedAt])
            ->andReturn($transactionDuration);

        $transactionBuilder = new MetricBuilder($formaterMock);
        $result             = $transactionBuilder->buildSpanMetrics($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toBe(
                [
                    [
                        'metricset' => [
                            'samples' => [
                                'transaction.breakdown.count'  => ['value' => 1],
                                'transaction.duration.sum.us'  => ['value' => 1],
                                'transaction.self_time.sum.us' => ['value' => 1],
                                'span.self_time.count'         => ['value' => 1],
                                'span.self_time.sum.us'        => ['value' => $expectedAppSum],
                            ],
                            'timestamp'   => $timestamp,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => 'app', 'subtype' => ''],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'transaction duration is int'   => [1000, 1000],
            'transaction duration is float' => [1000.5, 1000],
        ]
    );
