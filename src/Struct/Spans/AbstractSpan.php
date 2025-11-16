<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Traits\HasLabelContext;

abstract class AbstractSpan extends AbstractChildTraceEvent
{
    use HasLabelContext;

    public function __construct(
        AbstractChildTraceEvent $parentEvent,
        ?int $startAt = null,
        ?int $finishAt = null
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }
}
