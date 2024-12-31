<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;

class PlainSpan extends AbstractSpan
{
    public function __construct(
        public string $name,
        public string $type,
        AbstractChildTraceEvent $parentEvent,
        CarbonInterface $startAt,
        public ?string $subType = null,
        ?CarbonInterface $finishAt = null,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
