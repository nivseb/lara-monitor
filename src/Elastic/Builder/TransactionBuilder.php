<?php

namespace Nivseb\LaraMonitor\Elastic\Builder;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\Uri\Uri;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Contracts\Elastic\TransactionBuilderContract;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\DroppedSpanStats;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Tracing\ExternalTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

class TransactionBuilder implements TransactionBuilderContract
{
    public function __construct(
        protected ElasticFormaterContract $formater
    ) {}

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     * @param array<string, DroppedSpanStats>     $droppedSpanStats
     */
    public function buildTransactionRecords(
        AbstractTransaction $transaction,
        Collection $spans,
        array $droppedSpanStats
    ): array {
        $transactionRecord = $this->buildTransactionRecord($transaction, $spans, $droppedSpanStats);
        if (!$transactionRecord) {
            return [];
        }

        return [['transaction' => $transactionRecord]];
    }

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     * @param array<string, DroppedSpanStats>     $droppedSpanStats
     */
    protected function buildTransactionRecord(
        AbstractTransaction $transaction,
        Collection $spans,
        array $droppedSpanStats
    ): ?array {
        $transactionRecord = $this->buildTransactionRecordBase($transaction, $spans, $droppedSpanStats);
        if (!$transactionRecord) {
            return null;
        }

        $transactionRecord = array_merge_recursive(
            $transactionRecord,
            match (true) {
                $transaction instanceof RequestTransaction => $this->buildRequestAdditionalData($transaction),
                $transaction instanceof CommandTransaction => $this->buildCommandAdditionalData($transaction),
                $transaction instanceof JobTransaction     => $this->buildJobAdditionalData($transaction),
                default                                    => [],
            }
        );

        if (is_array($transactionRecord['context']) && !$transactionRecord['context']) {
            unset($transactionRecord['context']);
        }

        return $transactionRecord;
    }

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     * @param array<string, DroppedSpanStats>     $droppedSpanStats
     */
    protected function buildTransactionRecordBase(
        AbstractTransaction $transaction,
        Collection $spans,
        array $droppedSpanStats
    ): ?array {
        $duration = $transaction->getDuration();
        if ($transaction->startAt === null || $duration === null) {
            return null;
        }

        $trace        = $transaction->getTrace();
        $droppedCount = 0;
        $droppedStats = $this->buildDroppedSpanStats($droppedSpanStats, $droppedCount);

        return [
            'id'          => $transaction->getId(),
            'type'        => $this->formater->getTransactionType($transaction),
            'trace_id'    => $transaction->getTraceId(),
            'parent_id'   => $trace instanceof ExternalTrace ? $trace->getId() : null,
            'name'        => $transaction->getName(),
            'timestamp'   => $transaction->startAt,
            'duration'    => $duration / CarbonInterface::MICROSECONDS_PER_MILLISECOND,
            'sample_rate' => $trace instanceof StartTrace ? $trace->sampleRate : null,
            'sampled'     => $trace->isSampled(),
            'span_count'  => [
                'started' => $spans->count(),
                'dropped' => $droppedCount,
            ],
            'dropped_spans_stats' => $droppedStats,
            'outcome'             => $this->formater->getOutcome($transaction),
            'session'             => null,
            'context'             => array_filter(
                [
                    'custom' => $transaction->getCustomContext() ?: null,
                    'tags'   => $transaction->getLabels() ?: null,
                ]
            ),
        ];
    }

    protected function buildRequestAdditionalData(RequestTransaction $transaction): array
    {
        $data = [
            'result'  => 'HTTP '.substr((string) $transaction->responseCode, 0, 1).'xx',
            'context' => [
                'request' => [
                    'method' => $transaction->method,
                ],
                'response' => [
                    'status_code' => $transaction->responseCode,
                ],
            ],
        ];
        if ($transaction->httpVersion) {
            Arr::set($data, 'context.request.http_version', Str::after($transaction->httpVersion, '/'));
        }
        if ($transaction->requestHeaders) {
            Arr::set(
                $data,
                'context.request.headers',
                Arr::map(
                    Arr::except($transaction->requestHeaders, ['Cookie']),
                    static fn ($value) => is_array($value) && count($value) === 1 ? Arr::first($value) : $value
                )
            );
        }
        if ($transaction->requestCookies) {
            Arr::set($data, 'context.request.cookies', $transaction->requestCookies);
        }
        if ($transaction->responseHeaders) {
            Arr::set(
                $data,
                'context.response.headers',
                Arr::map(
                    $transaction->responseHeaders,
                    static fn ($value) => is_array($value) && count($value) === 1 ? Arr::first($value) : $value
                )
            );
        }
        $uri = $transaction->fullUrl ? Uri::new($transaction->fullUrl) : null;
        if ($uri) {
            $scheme = $uri->getScheme();
            if ($scheme) {
                $scheme .= ':';
            }
            $queryString = (string) $uri->getQuery();
            if ($queryString) {
                $queryString = '?'.$queryString;
            }
            $path = $uri->getPath();
            if (!Str::startsWith($path, '/')) {
                $path = '/'.$path;
            }
            $fragment = $uri->getFragment();
            if ($fragment && !Str::startsWith($fragment, '#')) {
                $fragment = '#'.$fragment;
            }

            Arr::set(
                $data,
                'context.request.url',
                array_filter(
                    [
                        'raw'      => $path.$queryString.$fragment,
                        'full'     => (string) $uri,
                        'protocol' => $scheme,
                        'hostname' => $uri->getHost(),
                        'pathname' => $path,
                        'search'   => $queryString,
                        'hash'     => $fragment,
                        'port'     => (string) $uri->getPort(),
                    ],
                    static fn ($value) => $value && strlen($value) <= 1024
                )
            );
        }

        return $data;
    }

    protected function buildCommandAdditionalData(CommandTransaction $transaction): array
    {
        return [
            'result' => (string) $transaction->exitCode,
        ];
    }

    protected function buildJobAdditionalData(JobTransaction $transaction): array
    {
        return [
            'result'  => $transaction->successful ? 'successful' : 'failed',
            'context' => array_filter(
                [
                    'tags' => array_filter(
                        [
                            'laravel_job_id'         => $transaction->jobId,
                            'laravel_job_connection' => $transaction->jobConnection,
                            'laravel_job_queue'      => $transaction->jobQueue,
                        ]
                    ),
                ]
            ) ?: null,
        ];
    }

    /**
     * @param array<string, DroppedSpanStats> $droppedSpanStats
     */
    protected function buildDroppedSpanStats(
        array $droppedSpanStats,
        int &$droppedCount
    ): ?array {
        if (!$droppedSpanStats) {
            return null;
        }

        $statsRecords = [];
        foreach ($droppedSpanStats as $stats) {
            if (!$stats->referenceSpan instanceof QuerySpan) {
                continue;
            }
            $droppedCount += $stats->count;
            $statsRecords[] = [
                // TODO: Add to formater or span builder and switch types
                'destination_service_resource' => $stats->referenceSpan->databaseType.'/'.$stats->referenceSpan->host,
                'service_target_type'          => $stats->referenceSpan->databaseType,
                'service_target_name'          => $stats->referenceSpan->database,
                'outcome'                      => $this->formater->getOutcome($stats->referenceSpan),
                'duration.count'               => $stats->count,
                'duration.sum.us'              => $stats->durationSum / CarbonInterface::MICROSECONDS_PER_MILLISECOND,
            ];
        }

        return $statsRecords;
    }
}
