<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
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
        CarbonInterface $startAt,
        ?CarbonInterface $finishAt = null,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return 'queueing '.$this->jobName;
    }

    public function getLabels(): ?array
    {
        $data = parent::getLabels() ?? [];
        Arr::set($data, 'laravel_job_id', $this->jobId);
        Arr::set($data, 'laravel_job_connection', $this->jobConnection);
        Arr::set($data, 'laravel_job_id', $this->jobId);
        return array_filter($data) ?: null ;
    }
}
