<?php

namespace Nivseb\LaraMonitor\Struct\Tracing;

class ExternalTrace extends AbstractTrace
{
    public function __construct(
        public readonly W3CTraceParent $w3cParent
    ) {}

    public function getId(): string
    {
        return $this->w3cParent->parentId;
    }

    public function getTraceId(): string
    {
        return $this->w3cParent->traceId;
    }

    public function isSampled(): bool
    {
        return $this->w3cParent->sampled();
    }
}
