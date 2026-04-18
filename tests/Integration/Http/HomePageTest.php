<?php

namespace Tests\Integration\Http;

test(
    'Access welcome page and send data to elastic apm server',
    function () {
        $response = $this->buildAppClient()->get('/');
        expect($response->getStatusCode())->toBe(200);

        $apmRequests = $this->getApmReceivedEventRequests();
        expect($apmRequests)->toHaveCount(1);
        $request = $apmRequests[0];
        expect($request)->toHaveCount(10);

        $this->assertMatchingMetaData(
            [
                'service' => ['name' => 'Laravel', 'framework' => ['name' => 'laravel/framework']],
                "cloud" => null,
                "labels" => null,
                "network" => null,
                "user" => null
            ],
            $request
        );
    }
);
