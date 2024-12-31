<?php

namespace Nivseb\LaraMonitor\Struct\Tracing;

use Nivseb\LaraMonitor\Struct\AbstractTraceEvent;

abstract class AbstractTrace extends AbstractTraceEvent
{
    public function getTrace(): AbstractTraceEvent
    {
        return $this;
    }
}
