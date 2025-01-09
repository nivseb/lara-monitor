<?php

namespace Nivseb\LaraMonitor\Struct;

use Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent;

abstract class AbstractTraceEvent
{
    abstract public function getId(): string;

    abstract public function getTrace(): AbstractTraceEvent;

    abstract public function getTraceId(): string;

    abstract public function isSampled(): bool;

    /**
     * @see https://www.w3.org/TR/trace-context/#traceparent-header
     */
    public function asW3CTraceParent(): W3CTraceParent
    {
        return new W3CTraceParent(
            '00',
            $this->getTraceId(),
            $this->getId(),
            $this->isSampled() ?
                sprintf('%02x', W3CTraceParent::SAMPLE_FLAG) :
                sprintf('%02x', W3CTraceParent::NO_FLAG)
        );
    }
}
