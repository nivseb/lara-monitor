<?php

namespace Nivseb\LaraMonitor\Elastic;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nivseb\LaraMonitor\Contracts\ApmAgentContract;
use Nivseb\LaraMonitor\Contracts\Elastic\ErrorBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\MetaBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\MetricBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\SpanBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\TransactionBuilderContract;
use Nivseb\LaraMonitor\Facades\LaraMonitorApm;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Throwable;

class ElasticAgent implements ApmAgentContract
{
    public function __construct(
        protected TransactionBuilderContract $transactionBuilder,
        protected SpanBuilderContract $spanBuilder,
        protected ErrorBuilderContract $errorBuilder,
        protected MetaBuilderContract $metaBuilder,
        protected MetricBuilderContract $metricBuilder,
    ) {}

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function sendData(AbstractTransaction $transaction, Collection $spans): bool
    {
        try {
            $records = $this->prepareRecords($transaction, $spans);
            $output  = $records ? $this->prepareOutput($records) : null;
            if (!$output) {
                return false;
            }

            return $this->sendToApmServer($output);
        } catch (Throwable $exception) {
            Log::error('Fail to build and send to APM-Server!', ['exception' => $exception]);

            return false;
        }
    }

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    protected function prepareRecords(AbstractTransaction $transaction, Collection $spans): array
    {
        $spanRecords = $this->spanBuilder->buildSpanRecords($transaction, $spans);
        if (!$spanRecords) {
            return [];
        }

        $transactionRecords = $this->transactionBuilder->buildTransactionRecords($transaction, $spans, $spanRecords);
        if (!$transactionRecords) {
            return [];
        }

        return array_filter(
            [
                ...$this->metaBuilder->buildMetaRecords($transaction), // metadata must send before transaction
                ...$transactionRecords,
                ...$spanRecords,
                ...$this->errorBuilder->buildErrorRecords($transaction, $spans),
                ...$this->metricBuilder->buildSpanMetrics($transaction, $spans),
            ]
        );
    }

    protected function prepareOutput(array $records): ?string
    {
        $output = '';
        foreach ($records as $record) {
            $recordString = json_encode($record);
            if (!$recordString) {
                return null;
            }
            $output .= $recordString.chr(10);
        }

        return $output;
    }

    /**
     * @throws ConnectionException
     */
    protected function sendToApmServer(string $output): bool
    {
        $response = Http::baseUrl(config('lara-monitor.elasticApm.apmServer'))
            ->withHeaders(
                [
                    'User-Agent' => static::getUserAgent(),
                    'Accept' => 'application/json',
                ]
            )
            ->withBody($output, 'application/x-ndjson')
            ->post('/intake/v2/events');

        if ($response->accepted()) {
            return true;
        }
        Log::warning(
            'APM-Server Response '.$response->status(),
            ['response' => json_decode($response->body())]
        );

        return false;
    }

    protected function getUserAgent(): string {
        return LaraMonitorApm::getAgentName()
            .' '.LaraMonitorApm::getVersion()
            .' / '.Config::get('lara-monitor.service.name', '')
            .' '.Config::get('lara-monitor.service.version', '');
    }
}
