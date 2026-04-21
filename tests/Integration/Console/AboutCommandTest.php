<?php

namespace Tests\Integration\Http;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Process\Process;

test(
    'Run About Command and send data to elastic apm server',
    /**
     * @throws GuzzleException
     */
    function () {
        $process = new Process(['vendor/bin/testbench', 'about']);
        $process->run();
        expect($process->isSuccessful())->toBeTrue();

        $apmRequests = $this->getApmReceivedEventRequests();
        expect($apmRequests)->toHaveCount(1);
        $request = $apmRequests[0];
        expect($request)->toHaveCount(6);

        $this->assertHasMetaData(
            [
                "service" => [
                    "name" => "Laravel",
                    "version" => null,
                    "environment" => "local",
                    "agent" => [
                        "name" => "lara-monitor"
                    ],
                    "language" => [
                        "name" => "php"
                    ],
                    "framework" => [
                        "name" => "laravel/framework"
                    ],
                    "runtime" => null
                ],
                "cloud" => null,
                "labels" => null,
                "network" => null,
                "process" => [
                    "argv" => [
                        'vendor/bin/testbench',
                        'about',

                    ]
                ],
                "system" => [],
                "user" => null
            ],
            $request
        );

        $this->assertHasTransactionData(
            [
                'name' => 'about',
                "type" => "command",
                "parent_id" => null,
                "sample_rate" => 1,
                "sampled" => true,
                "span_count" => [
                    "started" => 3,
                    "dropped" => 0,
                ],
                "dropped_spans_stats" => null,
                "outcome" => "success",
                "session" => null,
                "result" => "0",
            ],
            $request
        );

        $this->assertHasMetricsets(
            [
                [
                    "transaction" => [
                        "type" => "command",
                        "name" => "about",
                    ],
                    "span" => [
                        "type" => "app",
                        "subtype" => null,
                    ],
                    "samples" => [
                        "transaction.breakdown.count" => [],
                        "transaction.duration.sum.us" => [],
                        "transaction.self_time.sum.us" => [],
                        "span.self_time.count" => [],
                        "span.self_time.sum.us" => [],
                    ]
                ],
            ],
            $request);

        $this->assertHasSpan(
            [
                "name" => "booting",
                "type" => "boot",
                "subtype" => null,
                "action" => null,
                "sync" => true,
                "outcome" => null,
                "sample_rate" => 1
            ],
            $request
        );

        $this->assertHasSpan(
            [
                "name" => "run",
                "type" => "app",
                "subtype" => "handler",
                "action" => null,
                "sync" => true,
                "outcome" => null,
                "sample_rate" => 1
            ],
            $request
        );

        $this->assertHasSpan(
            [
                "name" => "terminating",
                "type" => "terminate",
                "subtype" => null,
                "action" => null,
                "sync" => true,
                "outcome" => null,
                "sample_rate" => 1
            ],
            $request
        );
    }
);
