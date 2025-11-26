<?php

namespace Tests\Component\Elastic\Builder;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Elastic\Builder\TransactionBuilder;
use Nivseb\LaraMonitor\Enums\Elastic\Outcome;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Tracing\ExternalTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

test(
    'add wrapper for transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $duration              = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('transaction');
    }
)
    ->with('all possible transaction types');

test(
    'dont build transaction record without duration',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturnNull();
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types');

test(
    'build transaction record duration that is zero',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn(0);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    }
)
    ->with('all possible transaction types');

test(
    'dont build transaction record without start',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $duration              = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturnNull();
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types');

test(
    'build span record if start that is zero',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $duration              = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn(0);
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    }
)
    ->with('all possible transaction types');

test(
    'add correct base data for transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $duration              = fake()->numberBetween(10000);
        $type                  = fake()->word();
        $outcome               = fake()->randomElement(Outcome::cases());
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);
        $formaterMock->allows('getTransactionType')->andReturn($type);
        $formaterMock->allows('getOutcome')->andReturn($outcome);

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'id'        => $transaction->getId(),
                    'type'      => $type,
                    'name'      => $transaction->getName(),
                    'timestamp' => $timestamp,
                    'duration'  => $duration,
                    'outcome'   => $outcome,
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct trace data to transaction record',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure() : AbstractTrace                                                                     $buildTraceEvent
     */
    function (Closure $buildTransaction, Closure $buildTraceEvent): void {
        $traceEvent  = $buildTraceEvent();
        $transaction = $buildTransaction(new Carbon(fake()->dateTime()), new Carbon(fake()->dateTime()), $traceEvent);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'trace_id'    => $traceEvent->getTraceId(),
                    'parent_id'   => $traceEvent instanceof ExternalTrace ? $traceEvent->getId() : null,
                    'sample_rate' => $traceEvent instanceof StartTrace ? $traceEvent->sampleRate : null,
                    'sampled'     => $traceEvent->isSampled(),
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all trace events');

test(
    'add correct span counts to transaction record',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (
        Closure $buildTransaction,
        Collection $spans,
        array $spanRecords,
        int $expectedStarted,
        int $expectedDropped
    ): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $duration              = fake()->numberBetween(10000);
        $type                  = fake()->word();
        $outcome               = fake()->randomElement(Outcome::cases());
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);
        $formaterMock->allows('getTransactionType')->andReturn($type);
        $formaterMock->allows('getOutcome')->andReturn($outcome);

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, $spans, $spanRecords);

        expect($result[0]['transaction'])
            ->toMatchArray(['span_count' => ['started' => $expectedStarted, 'dropped' => $expectedDropped]]);
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'no spans and no span records' => [new Collection(), [], 0, 0],
            'more dropped as exists'       => [new Collection(['test']), ['test', 'test'], 1, 0],
            'nothing dropped'              => [new Collection(['test', 'test']), ['test', 'test'], 2, 0],
            'drop some'                    => [new Collection(['test', 'test', 'test', 'test']), ['test', 'test'], 4, 2],
            'only records'                 => [new Collection([]), ['test'], 0, 0],
        ]
    );

test(
    'add correct additional data for request transaction for response code',
    function (int $responseCode, string $expectedResult): void {
        $transaction = new RequestTransaction(
            new StartTrace(true, 1.0),
            new Carbon(fake()->dateTime()),
            new Carbon(fake()->dateTime())
        );
        $transaction->responseCode = $responseCode;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'result'  => $expectedResult,
                    'context' => [
                        'request'  => ['method' => ''],
                        'response' => ['status_code' => $responseCode],
                    ],
                ]
            );
    }
)
    ->with(
        [
            'OK'                              => [200, 'HTTP 2xx'],
            'Created'                         => [201, 'HTTP 2xx'],
            'Accepted'                        => [202, 'HTTP 2xx'],
            'No Content'                      => [204, 'HTTP 2xx'],
            'Reset Content'                   => [205, 'HTTP 2xx'],
            'Partial Content'                 => [206, 'HTTP 2xx'],
            'Multiple Choices'                => [300, 'HTTP 3xx'],
            'Moved Permanently'               => [301, 'HTTP 3xx'],
            'Found'                           => [302, 'HTTP 3xx'],
            'See Other'                       => [303, 'HTTP 3xx'],
            'Not Modified'                    => [304, 'HTTP 3xx'],
            'Use Proxy'                       => [305, 'HTTP 3xx'],
            'Switch Proxy'                    => [306, 'HTTP 3xx'],
            'Temporary Redirect'              => [307, 'HTTP 3xx'],
            'Permanent Redirect'              => [308, 'HTTP 3xx'],
            'Bad Request'                     => [400, 'HTTP 4xx'],
            'Unauthorized'                    => [401, 'HTTP 4xx'],
            'Payment Required'                => [402, 'HTTP 4xx'],
            'Forbidden'                       => [403, 'HTTP 4xx'],
            'Not Found'                       => [404, 'HTTP 4xx'],
            'Method Not Allowed'              => [405, 'HTTP 4xx'],
            'Not Acceptable'                  => [406, 'HTTP 4xx'],
            'Proxy Authentication Required'   => [407, 'HTTP 4xx'],
            'Request Timeout'                 => [408, 'HTTP 4xx'],
            'Conflict'                        => [409, 'HTTP 4xx'],
            'Gone'                            => [410, 'HTTP 4xx'],
            'Length Required'                 => [411, 'HTTP 4xx'],
            'Precondition Failed'             => [412, 'HTTP 4xx'],
            'Payload Too Large'               => [413, 'HTTP 4xx'],
            'URI Too Long'                    => [414, 'HTTP 4xx'],
            'Unsupported Media Type'          => [415, 'HTTP 4xx'],
            'Range Not Satisfiable'           => [416, 'HTTP 4xx'],
            'Expectation Failed'              => [417, 'HTTP 4xx'],
            'Misdirected Request'             => [421, 'HTTP 4xx'],
            'Unprocessable Content'           => [422, 'HTTP 4xx'],
            'Too Early'                       => [425, 'HTTP 4xx'],
            'Upgrade Required'                => [426, 'HTTP 4xx'],
            'Precondition Required'           => [428, 'HTTP 4xx'],
            'Too Many Requests'               => [429, 'HTTP 4xx'],
            'Request Header Fields Too Large' => [431, 'HTTP 4xx'],
            'Internal Server Error'           => [500, 'HTTP 5xx'],
            'Not Implemented'                 => [501, 'HTTP 5xx'],
            'Bad Gateway'                     => [502, 'HTTP 5xx'],
            'Service Unavailable'             => [503, 'HTTP 5xx'],
            'Gateway Timeout'                 => [504, 'HTTP 5xx'],
            'HTTP Version Not Supported'      => [505, 'HTTP 5xx'],
            'Variant Also Negotiates'         => [506, 'HTTP 5xx'],
            'Not Extended'                    => [510, 'HTTP 5xx'],
            'Network Authentication Required' => [511, 'HTTP 5xx'],
        ]
    );

test(
    'add correct additional data for request transaction for request method',
    function (string $method): void {
        $transaction = new RequestTransaction(
            new StartTrace(true, 1.0),
            new Carbon(fake()->dateTime()),
            new Carbon(fake()->dateTime())
        );
        $transaction->method       = $method;
        $transaction->responseCode = 200;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'result'  => 'HTTP 2xx',
                    'context' => [
                        'request'  => ['method' => $method],
                        'response' => ['status_code' => 200],
                    ],
                ]
            );
    }
)
    ->with(
        [
            'GET'    => ['GET'],
            'POST'   => ['POST'],
            'PUT'    => ['PUT'],
            'DELETE' => ['DELETE'],
            'OPTION' => ['OPTION'],
            'HEAD'   => ['HEAD'],
        ]
    );

test(
    'add correct additional data for request transaction for http protocol version',
    function (string $givenVersion, string $expectedVersion): void {
        $transaction = new RequestTransaction(
            new StartTrace(true, 1.0),
            new Carbon(fake()->dateTime()),
            new Carbon(fake()->dateTime())
        );
        $transaction->method       = 'GET';
        $transaction->responseCode = 200;
        $transaction->httpVersion  = $givenVersion;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'result'  => 'HTTP 2xx',
                    'context' => [
                        'request'  => ['method' => 'GET', 'http_version' => $expectedVersion],
                        'response' => ['status_code' => 200],
                    ],
                ]
            );
    }
)
    ->with(
        [
            'HTTP/0.9' => ['HTTP/0.9', '0.9'],
            'HTTP/1.0' => ['HTTP/1.0', '1.0'],
            'HTTP/1.1' => ['HTTP/1.1', '1.1'],
            'HTTP/2'   => ['HTTP/2', '2'],
            'HTTP/3'   => ['HTTP/3', '3'],
        ]
    );

test(
    'add correct additional data for request transaction for request headers',
    function (array $givenHeaders, array $expectedHeaders): void {
        $transaction = new RequestTransaction(
            new StartTrace(true, 1.0),
            new Carbon(fake()->dateTime()),
            new Carbon(fake()->dateTime())
        );
        $transaction->method         = 'GET';
        $transaction->responseCode   = 200;
        $transaction->requestHeaders = $givenHeaders;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'result'  => 'HTTP 2xx',
                    'context' => [
                        'request'  => ['method' => 'GET', 'headers' => $expectedHeaders],
                        'response' => ['status_code' => 200],
                    ],
                ]
            );
    }
)
    ->with(
        [
            'Header with single value'           => [['test_header' => 'test_header_value'], ['test_header' => 'test_header_value']],
            'Header with single  value in array' => [['test_header' => ['test_header_value']], ['test_header' => 'test_header_value']],
            'Header with multiple values'        => [['test_header' => ['test_header_value_1', 'test_header_value_2']], ['test_header' => ['test_header_value_1', 'test_header_value_2']]],
        ]
    );

test(
    'add correct additional data for request transaction for url',
    function (string $givenUrl, array $expectedUrl): void {
        $transaction = new RequestTransaction(
            new StartTrace(true, 1.0),
            new Carbon(fake()->dateTime()),
            new Carbon(fake()->dateTime())
        );
        $transaction->method       = 'GET';
        $transaction->responseCode = 200;
        $transaction->fullUrl      = $givenUrl;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'result'  => 'HTTP 2xx',
                    'context' => [
                        'request'  => ['method' => 'GET', 'url' => $expectedUrl],
                        'response' => ['status_code' => 200],
                    ],
                ]
            );
    }
)
    ->with(
        [
            'only host' => [
                'https://localhost',
                [
                    'protocol' => 'https:',
                    'raw'      => '/',
                    'full'     => 'https://localhost',
                    'hostname' => 'localhost',
                    'pathname' => '/',
                ],
            ],
            'http' => [
                'http://localhost',
                [
                    'protocol' => 'http:',
                    'raw'      => '/',
                    'full'     => 'http://localhost',
                    'hostname' => 'localhost',
                    'pathname' => '/',
                ],
            ],
            'host with query string' => [
                'https://localhost?value=1&value=2',
                [
                    'protocol' => 'https:',
                    'raw'      => '/?value=1&value=2',
                    'full'     => 'https://localhost?value=1&value=2',
                    'hostname' => 'localhost',
                    'search'   => '?value=1&value=2',
                    'pathname' => '/',
                ],
            ],
            'host with path' => [
                'https://localhost/test/path',
                [
                    'protocol' => 'https:',
                    'raw'      => '/test/path',
                    'full'     => 'https://localhost/test/path',
                    'hostname' => 'localhost',
                    'pathname' => '/test/path',
                ],
            ],
            'host with frament' => [
                'https://localhost#anker',
                [
                    'protocol' => 'https:',
                    'raw'      => '/#anker',
                    'full'     => 'https://localhost#anker',
                    'hostname' => 'localhost',
                    'hash'     => '#anker',
                    'pathname' => '/',
                ],
            ],
            'full example' => [
                'https://www.example.com/p/a/t/h?query=string#hash',
                [
                    'protocol' => 'https:',
                    'full'     => 'https://www.example.com/p/a/t/h?query=string#hash',
                    'hostname' => 'www.example.com',
                    'pathname' => '/p/a/t/h',
                    'search'   => '?query=string',
                    'hash'     => '#hash',
                    'raw'      => '/p/a/t/h?query=string#hash',
                ],
            ],
        ]
    );

test(
    'add correct additional data for request transaction for request cookies',
    function (array $givenCookies, array $expectedCookies): void {
        $transaction = new RequestTransaction(
            new StartTrace(true, 1.0),
            new Carbon(fake()->dateTime()),
            new Carbon(fake()->dateTime())
        );
        $transaction->method         = 'GET';
        $transaction->responseCode   = 200;
        $transaction->requestCookies = $givenCookies;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'result'  => 'HTTP 2xx',
                    'context' => [
                        'request'  => ['method' => 'GET', 'cookies' => $expectedCookies],
                        'response' => ['status_code' => 200],
                    ],
                ]
            );
    }
)
    ->with(
        [
            'Cookie with single value'           => [['test_cookie' => 'test_cookie_value'], ['test_cookie' => 'test_cookie_value']],
            'Cookie with single  value in array' => [['test_cookie' => ['test_cookie_value']], ['test_cookie' => ['test_cookie_value']]],
            'Cookie with multiple values'        => [['test_cookie' => ['test_cookie_value_1', 'test_cookie_value_2']], ['test_cookie' => ['test_cookie_value_1', 'test_cookie_value_2']]],
        ]
    );

test(
    'add correct additional data for request transaction for response headers',
    function (array $givenHeaders, array $expectedHeaders): void {
        $transaction = new RequestTransaction(
            new StartTrace(true, 1.0),
            new Carbon(fake()->dateTime()),
            new Carbon(fake()->dateTime())
        );
        $transaction->method          = 'GET';
        $transaction->responseCode    = 200;
        $transaction->responseHeaders = $givenHeaders;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'result'  => 'HTTP 2xx',
                    'context' => [
                        'request'  => ['method' => 'GET'],
                        'response' => ['status_code' => 200, 'headers' => $expectedHeaders],
                    ],
                ]
            );
    }
)
    ->with(
        [
            'Header with single value'           => [['test_header' => 'test_header_value'], ['test_header' => 'test_header_value']],
            'Header with single  value in array' => [['test_header' => ['test_header_value']], ['test_header' => 'test_header_value']],
            'Header with multiple values'        => [['test_header' => ['test_header_value_1', 'test_header_value_2']], ['test_header' => ['test_header_value_1', 'test_header_value_2']]],
        ]
    );

test(
    'add correct additional data for command transaction',
    function (int $exitCode): void {
        $transaction = new CommandTransaction(
            new StartTrace(true, 1.0),
            new Carbon(fake()->dateTime()),
            new Carbon(fake()->dateTime())
        );
        $transaction->exitCode = $exitCode;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])->toMatchArray(['result' => $exitCode]);
    }
)
    ->with('error exit codes');

test(
    'add correct additional data for job transaction',
    function (bool $successful, string $expectedResult): void {
        $transaction = new JobTransaction(
            new StartTrace(true, 1.0),
            new Carbon(fake()->dateTime()),
            new Carbon(fake()->dateTime())
        );
        $transaction->successful = $successful;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->randomElement(Outcome::cases()));

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])->toMatchArray(['result' => $expectedResult]);
    }
)
    ->with(
        [
            'successful' => [true, 'successful'],
            'failed'     => [false, 'failed'],
        ]
    );
