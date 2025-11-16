<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;

class JobQueueingSpan extends AbstractSpan
{
    public ?string $jobId         = null;
    public ?string $jobConnection = null;
    public ?string $jobQueue      = null;
    public ?int $jobDelay         = null;

    public function __construct(
        public string $jobName,
        AbstractChildTraceEvent $parentEvent,
        int $startAt,
        ?int $finishAt = null,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return 'queueing '.$this->jobName;
    }
}
