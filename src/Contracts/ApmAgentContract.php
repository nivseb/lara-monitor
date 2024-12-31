<?php

namespace Nivseb\LaraMonitor\Contracts;

use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

interface ApmAgentContract
{
    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function sendData(AbstractTransaction $transaction, Collection $spans): bool;
}
