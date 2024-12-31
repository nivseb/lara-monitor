<?php

namespace Nivseb\LaraMonitor\Elastic\Builder;

use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Contracts\Elastic\TransactionBuilderContract;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Tracing\ExternalTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

class TransactionBuilder implements TransactionBuilderContract
{
    public function __construct(
        protected ElasticFormaterContract $formater
    ) {}

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function buildTransactionRecords(
        AbstractTransaction $transaction,
        Collection $spans,
        array $spanRecords
    ): array {
        $transactionRecord = $this->buildTransactionRecord($transaction, $spans->count(), count($spanRecords));
        if (!$transactionRecord) {
            return [];
        }

        return [['transaction' => $transactionRecord]];
    }

    protected function buildTransactionRecord(
        AbstractTransaction $transaction,
        int $totalSpanCount,
        int $spanRecordCount
    ): ?array {
        $transactionRecord = $this->buildTransactionRecordBase($transaction, $totalSpanCount, $spanRecordCount);
        if (!$transactionRecord) {
            return null;
        }

        return $transactionRecord;
    }

    protected function buildTransactionRecordBase(
        AbstractTransaction $transaction,
        int $totalSpanCount,
        int $spanRecordCount
    ): ?array {
        $timestamp = $this->formater->getTimestamp($transaction->startAt);
        $duration  = $this->formater->calcDuration($transaction->startAt, $transaction->finishAt);
        if ($timestamp === null || $duration === null) {
            return null;
        }

        $trace = $transaction->getTrace();

        return [
            'id'          => $transaction->id,
            'type'        => $this->formater->getTransactionType($transaction),
            'trace_id'    => $transaction->getTraceId(),
            'parent_id'   => $trace instanceof ExternalTrace ? $trace->getId() : null,
            'name'        => $transaction->getName(),
            'timestamp'   => $timestamp,
            'duration'    => $duration,
            'sample_rate' => $trace instanceof StartTrace ? $trace->sampleRate : null,
            'sampled'     => $trace->isSampled(),
            'span_count'  => [
                'started' => $totalSpanCount,
                'dropped' => max($totalSpanCount - $spanRecordCount, 0),
            ],
            'dropped_spans_stats' => null,
            'context'             => null,
            'outcome'             => $this->formater->getOutcome($transaction),
            'session'             => null,
        ];
    }
}
