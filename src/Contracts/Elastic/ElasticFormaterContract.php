<?php

namespace Nivseb\LaraMonitor\Contracts\Elastic;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

interface ElasticFormaterContract
{
    public function getSpanTypeData(AbstractSpan $span): ?TypeData;

    public function getTransactionType(AbstractTransaction $transaction): string;

    public function getOutcome(AbstractChildTraceEvent $traceEvent): ?string;

    public function getTimestamp(?CarbonInterface $date): ?int;

    public function calcDuration(?CarbonInterface $startDate, ?CarbonInterface $endDate): ?float;
}
