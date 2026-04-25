<?php

namespace Tests\Integration\Http;

use GuzzleHttp\Exception\GuzzleException;

test(
    'Access welcome page and send data to elastic apm server',
    /**
     * @throws GuzzleException
     */
    function (): void {
        $response = $this->buildAppClient()->get('/');
        expect($response->getStatusCode())->toBe(200);

        $apmRequests = $this->getApmReceivedEventRequests();
        expect($apmRequests)->toHaveCount(1);
        $request = $apmRequests[0];
        expect($request)->toHaveCount(10);

        $this->assertHasMetaData(
            [
                'service' => [
                    'name'        => 'Laravel',
                    'version'     => null,
                    'environment' => 'local',
                    'agent'       => [
                        'name' => 'lara-monitor',
                    ],
                    'language' => [
                        'name' => 'php',
                    ],
                    'framework' => [
                        'name' => 'laravel/framework',
                    ],
                    'runtime' => null,
                ],
                'cloud'   => null,
                'labels'  => null,
                'network' => null,
                'process' => [
                    'argv' => null,
                ],
                'system' => [],
                'user'   => null,
            ],
            $request
        );

        $this->assertHasTransactionData(
            [
                'name'        => 'GET /',
                'type'        => 'request',
                'parent_id'   => null,
                'sample_rate' => 1,
                'sampled'     => true,
                'span_count'  => [
                    'started' => 5,
                    'dropped' => 0,
                ],
                'dropped_spans_stats' => null,
                'outcome'             => 'success',
                'session'             => null,
                'context'             => [
                    'request' => [
                        'method'       => 'GET',
                        'http_version' => '1.1',
                        'url'          => [
                            'raw'      => '/',
                            'full'     => 'http://localhost:8765',
                            'protocol' => 'http:',
                            'hostname' => 'localhost',
                            'pathname' => '/',
                            'port'     => '8765',
                        ],
                    ],
                    'response' => [
                        'status_code' => 200,
                        'headers'     => [],
                    ],
                ],
                'result' => 'HTTP 2xx',
            ],
            $request
        );

        $this->assertHasMetricsets(
            [
                [
                    'transaction' => [
                        'type' => 'request',
                        'name' => 'GET /',
                    ],
                    'span' => [
                        'type'    => 'template',
                        'subtype' => 'view',
                    ],
                    'samples' => [
                        'transaction.breakdown.count'  => [],
                        'transaction.duration.sum.us'  => [],
                        'transaction.self_time.sum.us' => [],
                        'span.self_time.count'         => [],
                        'span.self_time.sum.us'        => [],
                    ],
                ],
                [
                    'transaction' => [
                        'type' => 'request',
                        'name' => 'GET /',
                    ],
                    'span' => [
                        'type'    => 'template',
                        'subtype' => 'response',
                    ],
                    'samples' => [
                        'transaction.breakdown.count'  => [],
                        'transaction.duration.sum.us'  => [],
                        'transaction.self_time.sum.us' => [],
                        'span.self_time.count'         => [],
                        'span.self_time.sum.us'        => [],
                    ],
                ],
                [
                    'transaction' => [
                        'type' => 'request',
                        'name' => 'GET /',
                    ],
                    'span' => [
                        'type'    => 'app',
                        'subtype' => null,
                    ],
                    'samples' => [
                        'transaction.breakdown.count'  => [],
                        'transaction.duration.sum.us'  => [],
                        'transaction.self_time.sum.us' => [],
                        'span.self_time.count'         => [],
                        'span.self_time.sum.us'        => [],
                    ],
                ],
            ],
            $request
        );

        $this->assertHasSpan(
            [
                'name'        => 'booting',
                'type'        => 'boot',
                'subtype'     => null,
                'action'      => null,
                'sync'        => true,
                'outcome'     => null,
                'sample_rate' => 1,
            ],
            $request
        );

        $this->assertHasSpan(
            [
                'name'        => 'run',
                'type'        => 'app',
                'subtype'     => 'handler',
                'action'      => null,
                'sync'        => true,
                'outcome'     => null,
                'sample_rate' => 1,
            ],
            $request
        );

        $this->assertHasSpan(
            [
                'name'        => 'render view',
                'type'        => 'template',
                'subtype'     => 'view',
                'action'      => 'render',
                'sync'        => true,
                'outcome'     => null,
                'sample_rate' => 1,
            ],
            $request
        );

        $this->assertHasSpan(
            [
                'name'        => 'render response',
                'type'        => 'template',
                'subtype'     => 'response',
                'action'      => 'render',
                'sync'        => true,
                'outcome'     => null,
                'sample_rate' => 1,
            ],
            $request
        );

        $this->assertHasSpan(
            [
                'name'        => 'terminating',
                'type'        => 'terminate',
                'subtype'     => null,
                'action'      => null,
                'sync'        => true,
                'outcome'     => null,
                'sample_rate' => 1,
            ],
            $request
        );
    }
);
