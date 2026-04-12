<?php

namespace Nivseb\LaraMonitor\Services;

use Composer\InstalledVersions;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Nivseb\LaraMonitor\Contracts\ApmAgentContract;
use Nivseb\LaraMonitor\Contracts\ApmServiceContract;
use Nivseb\LaraMonitor\Facades\LaraMonitorAnalyser;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\DroppedSpanStats;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

class ApmService implements ApmServiceContract
{
    public function getVersion(): ?string
    {
        return InstalledVersions::getVersion('nivseb/lara-monitor');
    }

    public function getAgentName(): ?string
    {
        return Config::get('lara-monitor.service.agentName');
    }

    public function allowErrorResponse(int $allowedExitCode): void
    {
        LaraMonitorStore::setAllowedExitCode($allowedExitCode);
    }

    public function finishCurrentTransaction(): bool
    {
        $transaction = LaraMonitorStore::getTransaction();

        /** @var null|Collection<array-key, AbstractSpan> $spans */
        $spans = LaraMonitorStore::getSpanList();
        if (!$transaction || !$spans) {
            return false;
        }

        LaraMonitorAnalyser::analyse($transaction, $spans, LaraMonitorStore::getAllowedExitCode());

        return $this->sendToApmServer(
            $transaction,
            $spans,
            LaraMonitorStore::getDroppedSpanStatsList() ?? []
        );
    }

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     * @param array<string, DroppedSpanStats>     $droppedSpanStats
     */
    protected function sendToApmServer(AbstractTransaction $transaction, Collection $spans, array $droppedSpanStats): bool
    {
        try {
            /** @var ApmAgentContract $apmService */
            $apmService = Container::getInstance()->get(ApmAgentContract::class);
            $apmService->sendData($transaction, $spans, $droppedSpanStats);
        } catch (BindingResolutionException) {
            return false;
        }

        return true;
    }
}
