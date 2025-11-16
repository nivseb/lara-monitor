<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;

class PlainSpan extends AbstractSpan
{
    public function __construct(
        public string $name,
        public string $type,
        AbstractChildTraceEvent $parentEvent,
        int $startAt,
        public ?string $subType = null,
        ?int $finishAt = null,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
