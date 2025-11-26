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
use Nivseb\LaraMonitor\Enums\Elastic\Outcome;
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
        $port        = ($port === 80 || $port === 443) ? $port + 1 : $port;
        $url         = $scheme.'://'.$host.':'.$port.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $transaction,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 599);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
        $port        = ($port === 80 || $port === 443) ? $port + 1 : $port;
        $url         = $scheme.'://'.$host.':'.$port.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $spanParent,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
        $port        = ($port === 80 || $port === 443) ? $port + 1 : $port;
        $url         = $scheme.'://'.$host.':'.$port.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $transaction,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
        $port        = ($port === 80 || $port === 443) ? $port + 1 : $port;
        $url         = $scheme.'://'.$host.':'.$port.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $spanParent,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
    'add context for http span for external domain with http scheme and port 80',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $host        = fake()->domainName();
        $url         = 'http://'.$host.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $transaction,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 599);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
                        'port'    => 80,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':80',
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct type data to http span context with other span as parent for external domain with http scheme and port 80',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $host        = fake()->domainName();
        $url         = 'http://'.$host.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $spanParent,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
                        'port'    => 80,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':80',
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add context for http span for internal host with http scheme and port 80',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $host        = fake()->domainWord();
        $url         = 'http://'.$host.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $transaction,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
                        'port'    => 80,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':80',
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct type data to http span with other span as parent for internal host with http scheme and port 80',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $host        = fake()->domainWord();
        $url         = 'http://'.$host.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $spanParent,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
                        'port'    => 80,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':80',
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add context for http span for external domain with https scheme and port 443',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $host        = fake()->domainName();
        $url         = 'https://'.$host.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $transaction,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 599);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
                        'port'    => 443,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':443',
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct type data to http span context with other span as parent for external domain with https scheme and port 443',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $host        = fake()->domainName();
        $url         = 'https://'.$host.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $spanParent,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
                        'port'    => 443,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':443',
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add context for http span for internal host with https scheme and port 443',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $host        = fake()->domainWord();
        $url         = 'https://'.$host.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $transaction,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
                        'port'    => 443,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':443',
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct type data to http span with other span as parent for internal host with https scheme and port 443',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     */
    function (Closure $buildTransaction, Closure $buildSpanParent): void {
        $method      = fake()->word();
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $host        = fake()->domainWord();
        $url         = 'https://'.$host.'/test/myFile?myParam=23r0';
        $span        = new HttpSpan(
            $method,
            new Uri($url),
            $spanParent,
            Carbon::now()->subSeconds(59)->format('Uu'),
            Carbon::now()->subSeconds(2)->format('Uu')
        );
        $span->responseCode = fake()->numberBetween(1, 9999);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getOutcome')->andReturn(Outcome::Success);

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
                        'port'    => 443,
                        'service' => [
                            'resource' => 'http/'.$host,
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'name' => $host.':443',
                        ],
                    ],
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');
