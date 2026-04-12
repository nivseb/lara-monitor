<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

class DroppedSpanStats
{
    public function __construct(
        public readonly string $hash,
        public readonly AbstractSpan $referenceSpan,
        public int $count,
        public int $durationSum
    ) {}
}
