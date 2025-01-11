<?php

namespace Tests\Component\Elastic\Builder\SpanBuilder;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Elastic\Builder\SpanBuilder;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

test(
    'add context for http span for external domain',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $scheme      = fake()->randomElement(['http', 'https']);
        $host        = fake()->domainName();
        $port        = fake()->numberBetween(1, 999);
        $url         = $scheme.'://'.$host.':'.$port.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $transaction,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->responseCode = fake()->numberBetween(1, 599);

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
                    'http' => [
                        'method'   => $span->method,
                        'url'      => $url,
                        'response' => [
                            'status_code' => $span->responseCode,
                        ],
                    ],
                    'destination' => [
                        'address' => $host,
                        'port'    => $port,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':'.$port,
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct type data to http span context with other span as parent for external domain',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $scheme      = fake()->randomElement(['http', 'https']);
        $host        = fake()->domainName();
        $port        = fake()->numberBetween(1, 999);
        $url         = $scheme.'://'.$host.':'.$port.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $spanParent,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

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
                    'http' => [
                        'method'   => $span->method,
                        'url'      => $url,
                        'response' => [
                            'status_code' => $span->responseCode,
                        ],
                    ],
                    'destination' => [
                        'address' => $host,
                        'port'    => $port,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':'.$port,
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add context for http span for internal host',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $scheme      = fake()->randomElement(['http', 'https']);
        $host        = fake()->domainWord();
        $port        = fake()->numberBetween(1, 999);
        $url         = $scheme.'://'.$host.':'.$port.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $transaction,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

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
                    'http' => [
                        'method'   => $span->method,
                        'url'      => $url,
                        'response' => [
                            'status_code' => $span->responseCode,
                        ],
                    ],
                    'destination' => [
                        'address' => $host,
                        'port'    => $port,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':'.$port,
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct type data to http span with other span as parent for internal host',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $scheme      = fake()->randomElement(['http', 'https']);
        $host        = fake()->domainWord();
        $port        = fake()->numberBetween(1, 999);
        $url         = $scheme.'://'.$host.':'.$port.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $spanParent,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

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
                    'http' => [
                        'method'   => $span->method,
                        'url'      => $url,
                        'response' => [
                            'status_code' => $span->responseCode,
                        ],
                    ],
                    'destination' => [
                        'address' => $host,
                        'port'    => $port,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':'.$port,
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');
