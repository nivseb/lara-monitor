<?php

namespace Nivseb\LaraMonitor\Contracts;

use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\AbstractTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

interface RepositoryContract
{
    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function setTransaction(AbstractTransaction $transaction, Collection $spans): bool;

    public function getTransaction(): ?AbstractTransaction;

    public function getCurrentTraceEvent(): ?AbstractChildTraceEvent;

    /**
     * @return ?Collection<array-key, AbstractSpan>
     */
    public function getSpanList(): ?Collection;

    public function addSpan(AbstractSpan $span): bool;

    public function setAllowedExitCode(?int $expectedValue): bool;

    public function getAllowedExitCode(): ?int;

    public function setCurrentTraceEvent(AbstractTraceEvent $traceEvent): bool;

    public function resetData(): bool;
}
