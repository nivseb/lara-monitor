<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Throwable;

class IntegrationTestCase extends BaseTestCase
{
    /**
     * @throws GuzzleException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $client = $this->buildMockClient();
        $client->put('/mockserver/reset');
        $client
            ->put(
                '/mockserver/expectation',
                [
                    'json' => [
                        'httpRequest' => [
                            'method' => 'POST',
                            'path'   => '/elastic-apm-server/intake/v2/events',
                        ],
                        'httpResponse' => ['statusCode' => 202],
                    ],
                ]
            );
    }

    protected function buildAppClient(): Client
    {
        return new Client(
            [
                'base_uri'    => 'http://localhost:8765',
                'http_errors' => false,
                'timeout'     => 5.0,
            ]
        );
    }

    protected function buildMockClient(): Client
    {
        return new Client(
            [
                'base_uri'    => 'http://mock',
                'http_errors' => true,
                'timeout'     => 5.0,
            ]
        );
    }

    /**
     * @throws GuzzleException
     */
    protected function getApmReceivedEventRequests(): array
    {
        $response = $this->buildMockClient()->put(
            '/mockserver/retrieve?type=REQUESTS',
            ['json' => ['path' => '/elastic-apm-server/intake/v2/events']]
        );

        $requests    = json_decode($response->getBody()->getContents(), true) ?? [];
        $apmRequests = [];
        foreach ($requests as $request) {
            $singleRequest = [];
            foreach (explode("\n", trim($request['body']['string'] ?? '')) as $line) {
                if ($line !== '') {
                    $singleRequest[] = json_decode($line, true);
                }
            }
            $apmRequests[] = $singleRequest;
        }

        return $apmRequests;
    }

    protected function assertHasMetaData(array $expected, array $intakesRequestPayload): void
    {
        $matches = 0;
        foreach ($intakesRequestPayload as $event) {
            if (array_keys($event) !== ['metadata']) {
                continue;
            }
            $this->toMatchRecursive($expected, $event['metadata']);
            ++$matches;
        }
        expect($matches)->toBe(1, 'Need exact one metadata event, '.$matches.' given!');
    }

    protected function assertHasTransactionData(array $expected, array $intakesRequestPayload): void
    {
        $matches = 0;
        foreach ($intakesRequestPayload as $event) {
            if (array_keys($event) !== ['transaction']) {
                continue;
            }
            $this->toMatchRecursive($expected, $event['transaction']);
            ++$matches;
        }
        expect($matches)->toBe(1, 'Need exact one transaction event, '.$matches.' given!');
    }

    protected function assertHasMetricsets(array $expectedSets, array $intakesRequestPayload): void
    {
        $matches = 0;
        foreach ($intakesRequestPayload as $event) {
            if (array_keys($event) !== ['metricset']) {
                continue;
            }
            foreach ($expectedSets as $expectedSet) {
                try {
                    $this->toMatchRecursive($expectedSet, $event['metricset']);
                    ++$matches;
                } catch (Throwable) {
                }
            }
        }
        $expectedCount = count($expectedSets);

        expect($matches)->toBe($expectedCount, 'Need exact '.$expectedCount.' metricset(s) event, '.$matches.' given!');
    }

    protected function assertHasSpan(array $expected, array $intakesRequestPayload): void
    {
        $matches = 0;
        foreach ($intakesRequestPayload as $event) {
            if (array_keys($event) !== ['span']) {
                continue;
            }

            try {
                $this->toMatchRecursive($expected, $event['span']);
                ++$matches;
            } catch (Throwable) {
            }
        }

        expect($matches)->toBe(1, 'Need exact one matching span event, '.$matches.' given!');
    }

    protected function toMatchRecursive(array $expected, mixed $given): void
    {
        foreach ($expected as $key => $expectedValue) {
            expect($given)
                ->toBeArray()
                ->toHaveKey($key);
            if (is_array($expectedValue)) {
                $this->toMatchRecursive($expectedValue, $given[$key]);
            } else {
                expect($given[$key])->toBe($expectedValue);
            }
        }
    }
}
