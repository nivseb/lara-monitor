<?php

namespace Tests\Component\Elastic\Builder;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Elastic\Builder\MetricBuilder;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

test(
    'build successful metric set',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan, int $transactionDuration, int $spanDuration, int $expectedAppSum, int $expectedSpanSum): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = $transactionStartAt->clone()->addMilliseconds($transactionDuration);
        $transactionType       = fake()->word();
        $spanType              = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);
        $span                  = $buildSpan($transaction);
        $span->startAt         = (new Carbon(fake()->dateTime()))->format('Uu');
        $span->finishAt        = $span->startAt + ($spanDuration * 1000);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTransactionType')
            ->once()
            ->withArgs([$transaction])
            ->andReturn($transactionType);
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
                            'timestamp'   => $transaction->startAt,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => $spanType, 'subtype' => null],
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
                            'timestamp'   => $transaction->startAt,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => 'app', 'subtype' => null],
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
            '50%/50%' => [1000, 500, 500, 500],
        ]
    );

test(
    'dont build metric sets without transaction timestamp',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt = new Carbon(fake()->dateTime());
        $transaction        = $buildTransaction($transactionStartAt, null);
        $span               = $buildSpan($transaction);
        $span->startAt      = (new Carbon(fake()->dateTime()))->format('Uu');
        $span->finishAt     = (new Carbon(fake()->dateTime()))->format('Uu');

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);

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
    function (Closure $buildTransaction, Closure $buildSpan, int $transactionDuration, int $expectedAppSum): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = $transactionStartAt->clone()->addMilliseconds($transactionDuration);
        $transactionType       = fake()->word();
        $spanType              = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);
        $span                  = $buildSpan($transaction);
        $span->startAt         = (new Carbon(fake()->dateTime()))->format('Uu');
        $span->finishAt        = null;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTransactionType')
            ->once()
            ->withArgs([$transaction])
            ->andReturn($transactionType);
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
                            'timestamp'   => $transaction->startAt,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => 'app', 'subtype' => null],
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
            'transaction duration is int' => [1000, 1000],
        ]
    );

test(
    'build app metric without spans',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, int $transactionDuration, int $expectedAppSum): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = $transactionStartAt->clone()->addMilliseconds($transactionDuration);
        $transactionType       = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTransactionType')
            ->once()
            ->withArgs([$transaction])
            ->andReturn($transactionType);

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
                            'timestamp'   => $transaction->startAt,
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => 'app', 'subtype' => null],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'transaction duration is int' => [1000, 1000],
        ]
    );

test(
    'ignore system spans',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, int $transactionDuration, int $expectedAppSum): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = $transactionStartAt->clone()->addMilliseconds($transactionDuration);
        $transactionType       = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        $span = new SystemSpan(
            'test',
            'test',
            $transaction,
            $transactionStartAt->format('Uu'),
            $transactionFinishedAt->format('Uu')
        );

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock
            ->allows('getTransactionType')
            ->once()
            ->withArgs([$transaction])
            ->andReturn($transactionType);

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
                            'timestamp'   => (int) $transactionStartAt->format('Uu'),
                            'transaction' => ['type' => $transactionType, 'name' => $transaction->getName()],
                            'span'        => ['type' => 'app', 'subtype' => null],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'transaction duration is int' => [1000, 1000],
        ]
    );
