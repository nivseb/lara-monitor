<?php

namespace Nivseb\LaraMonitor\Contracts;

use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

interface AnalyserContract
{
    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function analyse(AbstractTransaction $transaction, Collection $spans, ?int $allowedExitCode): void;
}
