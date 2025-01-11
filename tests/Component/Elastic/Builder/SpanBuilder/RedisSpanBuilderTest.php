<?php

namespace Tests\Component\Elastic\Builder\SpanBuilder;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Elastic\Builder\SpanBuilder;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

test(
    'add context for redis span',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $statement   = (string) fake()->words(10, true);
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span        = new RedisCommandSpan(
            'SELECT',
            $statement,
            $transaction,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->host = fake()->word();
        $span->port         = fake()->numberBetween(1,2048);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toHaveKey('context')
            ->and($result[0]['span']['context'])
            ->toBeArray()
            ->toBe(
                [
                    'db' => [
                        'instance'  => $span->host,
                        'statement' => $span->statement,
                        'type'      => 'redis',
                    ],
                    'destination' => [
                        'address'  => $span->host,
                        'port'  => $span->port,
                        'service' => [
                            'resource' => 'redis/'.$span->host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $span->host,
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct type data to redis span with other span as parent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $statement   = (string) fake()->words(10, true);
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $span        = new RedisCommandSpan(
            'SELECT',
            $statement,
            $spanParent,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->host = fake()->word();
        $span->port         = fake()->numberBetween(1,2048);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toHaveKey('context')
            ->and($result[0]['span']['context'])
            ->toBeArray()
            ->toBe(
                [
                    'db' => [
                        'instance'  => $span->host,
                        'statement' => $span->statement,
                        'type'      => 'redis',
                    ],
                    'destination' => [
                        'address'  => $span->host,
                        'port'  => $span->port,
                        'service' => [
                            'resource' => 'redis/'.$span->host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $span->host,
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');
