<?php

namespace Nivseb\LaraMonitor\Exceptions;

use Nivseb\LaraMonitor\Contracts\Collector\Transaction\TransactionCollectorContract;

class WrongEventException extends AbstractException
{
    /**
     * @param class-string<TransactionCollectorContract> $collectorClass
     * @param class-string                               $requiredEventClass
     * @param class-string                               $eventClass
     */
    public function __construct(string $collectorClass, string $requiredEventClass, string $eventClass)
    {
        parent::__construct(
            '`'.$collectorClass.'` must called with `'
            .$requiredEventClass.'` event, but called with `'.$eventClass.'`!'
        );
    }
}
