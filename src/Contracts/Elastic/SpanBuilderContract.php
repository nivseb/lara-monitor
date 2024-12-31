<?php

namespace Nivseb\LaraMonitor\Contracts\Elastic;

use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

interface SpanBuilderContract
{
    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function buildSpanRecords(AbstractTransaction $transaction, Collection $spans): array;
}
