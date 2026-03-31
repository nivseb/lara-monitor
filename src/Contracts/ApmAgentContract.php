<?php

namespace Nivseb\LaraMonitor\Contracts;

use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\DroppedSpanStats;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

interface ApmAgentContract
{
    /**
     * @param Collection<array-key, AbstractSpan> $spans
     * @param array<string, DroppedSpanStats> $droppedSpanStats
     */
    public function sendData(AbstractTransaction $transaction, Collection $spans, array $droppedSpanStats): bool;
}
