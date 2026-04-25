<?php

namespace Tests\Integration\Http;

use GuzzleHttp\Exception\GuzzleException;

test(
    'load users like from an api endpoint and see database query as span',
    /**
     * @throws GuzzleException
     */
    function (): void {
        $response = $this->buildAppClient()->get('/users');
        expect($response->getStatusCode())->toBe(200);

        $apmRequests = $this->getApmReceivedEventRequests();
        expect($apmRequests)->toHaveCount(1);
        $request = $apmRequests[0];
        expect($request)->toHaveCount(11);

        $this->assertHasTransactionData(
            [
                'name' => 'GET /users',
                'type' => 'request',
            ],
            $request
        );

        $this->assertHasSpan(
            [
                'name'        => 'SELECT FROM users',
                'type'        => 'db',
                'subtype'     => 'sqlite',
                'action'      => 'query',
                'sync'        => true,
                'outcome'     => 'success',
                'sample_rate' => 1,
                'context'     => [
                    'db' => [
                        'statement' => 'select * from "users"',
                        'type'      => 'sql',
                    ],
                    'destination' => [
                        'address' => 'missing',
                        'port'    => null,
                        'service' => [
                            'resource' => 'sqlite/missing',
                        ],
                    ],
                    'service' => [
                        'target' => [
                            'type' => 'sqlite',
                        ],
                    ],
                ],
            ],
            $request
        );
    }
);
