<?php

namespace Nivseb\LaraMonitor\Elastic\Builder;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
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
        $transactionDuration = $transaction->getDuration();
        if ($transactionDuration === null || $transaction->startAt === null || $transaction->finishAt === null) {
            return [];
        }

        $transactionName   = $transaction->getName();
        $transactionType   = $this->formater->getTransactionType($transaction);
        $calculations      = $this->calcMetrics($spans);
        $spanMetrics       = [];
        $spanTotalDuration = 0.0;
        foreach ($calculations as $typeString => $durations) {
            $typeData = explode('.', $typeString);
            $spanTotalDuration += array_sum($durations);
            $spanMetrics[] = [
                'metricset' => $this->buildMetricRecord(
                    $transactionName,
                    $transactionType,
                    $transaction->startAt,
                    (string) Arr::first($typeData),
                    Arr::last($typeData) ?: null,
                    $durations
                ),
            ];
        }
        $spanMetrics[] = [
            'metricset' => $this->buildMetricRecord(
                $transactionName,
                $transactionType,
                $transaction->startAt,
                'app',
                null,
                [round($transactionDuration - $spanTotalDuration, 3)]
            ),
        ];

        return $spanMetrics;
    }

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     *
     * @return array<string, array<string, float>>
     */
    protected function calcMetrics(Collection $spans): array
    {
        $metrics = [];
        foreach ($spans as $span) {
            if ($span instanceof SystemSpan) {
                continue;
            }
            $duration = $span->getDuration();
            $typeData = $this->formater->getSpanTypeData($span);
            if ($duration === null || !$typeData) {
                continue;
            }
            $key             = $typeData->type.'.'.$typeData->subType;
            $metrics[$key][] = $duration;
        }

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
                'span.self_time.sum.us'        => [
                    'value' => (int) floor(array_sum($durations) / CarbonInterface::MICROSECONDS_PER_MILLISECOND),
                ],
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
