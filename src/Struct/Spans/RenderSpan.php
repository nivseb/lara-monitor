<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;

class RenderSpan extends AbstractSpan
{
    public function __construct(
        public string $type,
        AbstractChildTraceEvent $parentEvent,
        int $startAt,
        ?int $finishAt = null
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return 'render '.$this->type;
    }
}
