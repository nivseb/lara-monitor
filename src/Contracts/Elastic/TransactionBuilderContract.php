<?php

namespace Nivseb\LaraMonitor\Contracts\Elastic;

use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

interface TransactionBuilderContract
{
    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function buildTransactionRecords(
        AbstractTransaction $transaction,
        Collection $spans,
        array $spanRecords
    ): array;
}
