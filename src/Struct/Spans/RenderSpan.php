<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;

class RenderSpan extends AbstractSpan
{
    public function __construct(
        public string $type,
        AbstractChildTraceEvent $parentEvent,
        CarbonInterface $startAt,
        ?CarbonInterface $finishAt = null
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return 'render '.$this->type;
    }
}
