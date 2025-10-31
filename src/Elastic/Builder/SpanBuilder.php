<?php

namespace Nivseb\LaraMonitor\Elastic\Builder;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Contracts\Elastic\SpanBuilderContract;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\JobQueueingSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

class SpanBuilder implements SpanBuilderContract
{
    public function __construct(
        protected ElasticFormaterContract $formater
    ) {}

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function buildSpanRecords(AbstractTransaction $transaction, Collection $spans): array
    {
        if (!$transaction->startAt || !$transaction->finishAt) {
            return [];
        }
        $spanRecords = [];
        foreach ($spans as $span) {
            $spanRecord = $this->buildSpanRecord($span, $transaction->startAt);
            if (!$spanRecord) {
                continue;
            }
            $spanRecords[] = ['span' => $spanRecord];
        }

        $spanRecords = array_filter($spanRecords);
        if (!$spanRecords) {
            return [];
        }

        return $spanRecords;
    }

    protected function buildSpanRecord(AbstractSpan $span, CarbonInterface $transactionStart): ?array
    {
        $spanRecord = $this->buildSpanRecordBase($span, $transactionStart);
        if (!$spanRecord) {
            return null;
        }

        return array_merge_recursive(
            $spanRecord,
            match (true) {
                $span instanceof QuerySpan        => $this->buildQuerySpanAdditionalData($span),
                $span instanceof RedisCommandSpan => $this->buildRedisCommandSpanAdditionalData($span),
                $span instanceof HttpSpan         => $this->buildHttpSpanAdditionalData($span),
                $span instanceof JobQueueingSpan  => $this->buildJobSpanAdditionalData($span),
                default                           => [],
            }
        );
    }

    protected function buildSpanRecordBase(AbstractSpan $span, CarbonInterface $transactionStart): ?array
    {
        $timestamp = $this->formater->getTimestamp($span->startAt);
        $typeData  = $this->formater->getSpanTypeData($span);
        $duration  = $this->formater->calcDuration($span->startAt, $span->finishAt);
        $start     = $this->formater->calcDuration($transactionStart, $span->startAt);
        if (!$typeData || !$timestamp || $duration === null || $start === null) {
            return null;
        }

        return [
            'id'          => $span->getId(),
            'parent_id'   => $span->parentEvent->getId(),
            'trace_id'    => $span->getTraceId(),
            'name'        => $span->getName(),
            'timestamp'   => $timestamp,
            'duration'    => $duration,
            'start'       => $start,
            'type'        => $typeData->type,
            'subtype'     => $typeData->subType,
            'action'      => $typeData->action,
            'sync'        => true,
            'outcome'     => $this->formater->getOutcome($span),
            'sample_rate' => 1,
            'context'     => array_filter(
                [
                    'tags' => $span->getLabels() ?: null,
                ]
            ) ?: null,
        ];
    }

    protected function buildQuerySpanAdditionalData(QuerySpan $span): array
    {
        return [
            'context' => [
                'db' => [
                    'instance'  => $span->host,
                    'statement' => $span->sqlStatement,
                    'type'      => 'sql',
                ],
                'destination' => [
                    'address' => $span->host,
                    'port'    => $span->port,
                    'service' => [
                        'resource' => $span->databaseType.'/'.$span->host,
                    ],
                ],
                'service' => [
                    'target' => [
                        'name' => $span->host,
                    ],
                ],
            ],
        ];
    }

    protected function buildRedisCommandSpanAdditionalData(RedisCommandSpan $span): array
    {
        return [
            'context' => [
                'db' => [
                    'instance'  => $span->host,
                    'statement' => $span->statement,
                    'type'      => 'redis',
                ],
                'destination' => [
                    'address' => $span->host,
                    'port'    => $span->port,
                    'service' => [
                        'resource' => 'redis/'.$span->host,
                    ],
                ],
                'service' => [
                    'target' => [
                        'name' => $span->host,
                    ],
                ],
            ],
        ];
    }

    protected function buildHttpSpanAdditionalData(HttpSpan $span): array
    {
        return [
            'context' => [
                'http' => [
                    'method'   => $span->method,
                    'url'      => (string) $span->uri,
                    'response' => [
                        'status_code' => $span->responseCode,
                    ],
                ],
                'destination' => [
                    'address' => $span->getHost(),
                    'port'    => $span->getPort(),
                    'service' => [
                        'resource' => 'http/'.$span->getHost(),
                    ],
                ],
                'service' => [
                    'target' => [
                        'name' => $span->getHost().':'.$span->getPort(),
                    ],
                ],
            ],
        ];
    }

    protected function buildJobSpanAdditionalData(JobQueueingSpan $span): array
    {
        return [
            'context' => array_filter(
                [
                    'tags' => array_filter(
                        [
                            'laravel_job_id'         => $span->jobId,
                            'laravel_job_connection' => $span->jobConnection,
                            'laravel_job_queue'      => $span->jobQueue,
                        ]
                    ),
                ]
            ) ?: null,
        ];
    }
}
