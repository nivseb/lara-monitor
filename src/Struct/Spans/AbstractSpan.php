<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Traits\HasLabelContext;

abstract class AbstractSpan extends AbstractChildTraceEvent
{
    use HasLabelContext;

    public function __construct(
        AbstractChildTraceEvent $parentEvent,
        ?CarbonInterface        $startAt = null,
        ?CarbonInterface        $finishAt = null
    )
    {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }
}
