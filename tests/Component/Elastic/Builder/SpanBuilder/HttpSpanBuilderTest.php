<?php

namespace Tests\Component\Elastic\Builder\SpanBuilder;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Elastic\Builder\SpanBuilder;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Mockery;
use Mockery\MockInterface;

test(
    'add context for http span for external domain',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $method      = fake()->word();
        $path        = fake()->filePath();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span        = new HttpSpan(
            $method,
            $path,
            $transaction,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->scheme       = fake()->word();
        $span->host         = fake()->domainName();
        $span->port         = fake()->numberBetween(1, 9999);
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
                        'url'      => $span->scheme.'://'.$span->host,
                        'response' => [
                            'headers'           => null,
                            'status_code'       => $span->responseCode,
                            'transfer_size'     => null,
                            'decoded_body_size' => null,
                            'encoded_body_size' => null,
                        ],
                        'destination' => [
                            'address' => $span->host,
                            'port'    => $span->port,
                            'service' => [
                                'name'     => '//'.$span->host,
                                'resource' => $span->host,
                                'type'     => 'external',
                            ],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct type data to http span with other span as parent for external domain',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $method      = fake()->word();
        $path        = fake()->filePath();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $span        = new HttpSpan(
            $method,
            $path,
            $spanParent,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->scheme       = fake()->word();
        $span->host         = fake()->domainName();
        $span->port         = fake()->numberBetween(1, 9999);
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
                        'url'      => $span->scheme.'://'.$span->host,
                        'response' => [
                            'headers'           => null,
                            'status_code'       => $span->responseCode,
                            'transfer_size'     => null,
                            'decoded_body_size' => null,
                            'encoded_body_size' => null,
                        ],
                        'destination' => [
                            'address' => $span->host,
                            'port'    => $span->port,
                            'service' => [
                                'name'     => '//'.$span->host,
                                'resource' => $span->host,
                                'type'     => 'external',
                            ],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add service for http span for external domain',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $method      = fake()->word();
        $path        = fake()->filePath();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span        = new HttpSpan(
            $method,
            $path,
            $transaction,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->scheme       = fake()->word();
        $span->host         = fake()->domainName();
        $span->port         = fake()->numberBetween(1, 9999);
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
            ->toHaveKey('service')
            ->and($result[0]['span']['service'])
            ->toBeArray()
            ->toBe(
                [
                    'target' => [
                        'name' => $span->host,
                        'type' => 'http',
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add service type data to http span with other span as parent for external domain',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $method      = fake()->word();
        $path        = fake()->filePath();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $span        = new HttpSpan(
            $method,
            $path,
            $spanParent,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->scheme       = fake()->word();
        $span->host         = fake()->domainName();
        $span->port         = fake()->numberBetween(1, 9999);
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
            ->toHaveKey('service')
            ->and($result[0]['span']['service'])
            ->toBeArray()
            ->toBe(
                [
                    'target' => [
                        'name' => $span->host,
                        'type' => 'http',
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
        $path        = fake()->filePath();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span        = new HttpSpan(
            $method,
            $path,
            $transaction,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->scheme       = fake()->word();
        $span->host         = fake()->domainWord();
        $span->port         = fake()->numberBetween(1, 9999);
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
                        'url'      => $span->scheme.'://'.$span->host.'.localhost',
                        'response' => [
                            'headers'           => null,
                            'status_code'       => $span->responseCode,
                            'transfer_size'     => null,
                            'decoded_body_size' => null,
                            'encoded_body_size' => null,
                        ],
                        'destination' => [
                            'address' => $span->host,
                            'port'    => $span->port,
                            'service' => [
                                'name'     => '//'.$span->host,
                                'resource' => $span->host,
                                'type'     => 'external',
                            ],
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
        $path        = fake()->filePath();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $span        = new HttpSpan(
            $method,
            $path,
            $spanParent,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->scheme       = fake()->word();
        $span->host         = fake()->domainWord();
        $span->port         = fake()->numberBetween(1, 9999);
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
                        'url'      => $span->scheme.'://'.$span->host.'.localhost',
                        'response' => [
                            'headers'           => null,
                            'status_code'       => $span->responseCode,
                            'transfer_size'     => null,
                            'decoded_body_size' => null,
                            'encoded_body_size' => null,
                        ],
                        'destination' => [
                            'address' => $span->host,
                            'port'    => $span->port,
                            'service' => [
                                'name'     => '//'.$span->host,
                                'resource' => $span->host,
                                'type'     => 'external',
                            ],
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add service for http span for internal host',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $method      = fake()->word();
        $path        = fake()->filePath();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span        = new HttpSpan(
            $method,
            $path,
            $transaction,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->scheme       = fake()->word();
        $span->host         = fake()->domainWord();
        $span->port         = fake()->numberBetween(1, 9999);
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
            ->toHaveKey('service')
            ->and($result[0]['span']['service'])
            ->toBeArray()
            ->toBe(
                [
                    'target' => [
                        'name' => $span->host,
                        'type' => 'http',
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add service type data to http span with other span as parent for internal host',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $method      = fake()->word();
        $path        = fake()->filePath();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $span        = new HttpSpan(
            $method,
            $path,
            $spanParent,
            Carbon::now()->subSeconds(59),
            Carbon::now()->subSeconds(2)
        );
        $span->scheme       = fake()->word();
        $span->host         = fake()->domainWord();
        $span->port         = fake()->numberBetween(1, 9999);
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
            ->toHaveKey('service')
            ->and($result[0]['span']['service'])
            ->toBeArray()
            ->toBe(
                [
                    'target' => [
                        'name' => $span->host,
                        'type' => 'http',
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');
