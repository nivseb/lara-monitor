<?php

namespace Nivseb\LaraMonitor\Elastic;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Nivseb\LaraMonitor\Contracts\ApmAgentContract;
use Nivseb\LaraMonitor\Contracts\Elastic\ErrorBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\MetaBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\MetricBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\SpanBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\TransactionBuilderContract;
use Nivseb\LaraMonitor\Facades\LaraMonitorApm;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Traits\HasLogging;
use Throwable;

class ElasticAgent implements ApmAgentContract
{
    use HasLogging;

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
            $this->logForLaraMonitorFail('Fail to build and send to APM-Server!', $exception);

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
        $response = Http::baseUrl(config('lara-monitor.elasticApm.baseUrl'))
            ->withHeaders($this->buildHeaders())
            ->withBody($output, 'application/x-ndjson')
            ->post('/intake/v2/events');

        if ($response->accepted()) {
            return true;
        }
        $this->logForLaraMonitor(
            'Elastic APM-Server responses not with accepted!',
            [
                'status'   => $response->getStatusCode(),
                'response' => json_decode($response->body()),
            ]
        );

        return false;
    }

    protected function buildHeaders(): array
    {
        $headers = [
            'User-Agent' => static::getUserAgent(),
            'Accept'     => 'application/json',
        ];
        $authHeader = $this->buildAuthHeader();
        if ($authHeader) {
            $headers['Authorization'] = $authHeader;
        }

        return $headers;
    }

    protected function buildAuthHeader(): ?string
    {
        if ($token = config('lara-monitor.elasticApm.secretToken')) {
            return 'Bearer '.$token;
        }

        return null;
    }

    protected function getUserAgent(): string
    {
        return LaraMonitorApm::getAgentName()
            .' '.LaraMonitorApm::getVersion()
            .' / '.Config::get('lara-monitor.service.name', '')
            .' '.Config::get('lara-monitor.service.version', '');
    }
}
