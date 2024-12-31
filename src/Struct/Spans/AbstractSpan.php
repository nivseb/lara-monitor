<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;

abstract class AbstractSpan extends AbstractChildTraceEvent
{
    public function __construct(
        AbstractChildTraceEvent $parentEvent,
        ?CarbonInterface $startAt = null,
        ?CarbonInterface $finishAt = null
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }
}
