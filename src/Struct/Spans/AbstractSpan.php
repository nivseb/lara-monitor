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
        ?int                    $startAt = null,
        ?int                    $finishAt = null
    )
    {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    protected function getCompareData(): array
    {
        return [];
    }

    public function getSpanHash(): ?string
    {
        $data = $this->getCompareData();
        if (!$data) {
            return null;
        }
        return md5(static::class . serialize($data).$this->successful);
    }
}
