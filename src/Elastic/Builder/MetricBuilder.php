<?php

namespace Nivseb\LaraMonitor\Elastic\Builder;

use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Contracts\Elastic\MetricBuilderContract;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

class MetricBuilder implements MetricBuilderContract
{
    public function __construct(
        protected ElasticFormaterContract $formater
    ) {}

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function buildSpanMetrics(AbstractTransaction $transaction, Collection $spans): array
    {
        $transactionDuration = $this->formater->calcDuration($transaction->startAt, $transaction->finishAt);
        if ($transactionDuration === null || $transaction->startAt === null) {
            return [];
        }

        $transactionName = $transaction->getName();
        $transactionType = $this->formater->getTransactionType($transaction);
        $calculations    = $this->calcMetrics($spans, $transactionDuration);
        $spanMetrics     = [];
        foreach ($calculations as $type => $typeCalculations) {
            foreach ($typeCalculations as $subtype => $durations) {
                $spanMetrics[] = [
                    'metricset' => $this->buildMetricRecord(
                        $transactionName,
                        $transactionType,
                        $transaction->startAt,
                        $type,
                        $subtype,
                        $durations
                    ),
                ];
            }
        }

        return $spanMetrics;
    }

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     *
     * @return array<string, array<string, array<array-key, float>>>
     */
    protected function calcMetrics(Collection $spans, float $transactionDuration): array
    {
        $spanTotalDuration = 0.0;
        $metrics           = [];
        foreach ($spans as $span) {
            if ($span instanceof SystemSpan) {
                continue;
            }
            $duration = $this->formater->calcDuration($span->startAt, $span->finishAt);
            $typeData = $this->formater->getSpanTypeData($span);
            if ($duration === null || !$typeData) {
                continue;
            }
            $spanTotalDuration += $duration;
            $metrics[$typeData->type][$typeData->subType][] = $duration;
        }
        $metrics['app'] = [null => [round($transactionDuration - $spanTotalDuration, 3)]];

        return $metrics;
    }

    protected function buildMetricRecord(
        string $transactionName,
        string $transactionType,
        int $transactionTimestamp,
        string $type,
        ?string $subType,
        array $durations
    ): array {
        return [
            'samples' => [
                'transaction.breakdown.count'  => ['value' => 1],
                'transaction.duration.sum.us'  => ['value' => 1],
                'transaction.self_time.sum.us' => ['value' => 1],
                'span.self_time.count'         => ['value' => count($durations)],
                'span.self_time.sum.us'        => ['value' => (int) array_sum($durations)],
            ],
            'timestamp'   => $transactionTimestamp,
            'transaction' => [
                'type' => $transactionType,
                'name' => $transactionName,
            ],
            'span' => [
                'type'    => $type,
                'subtype' => $subType,
            ],
        ];
    }
}
