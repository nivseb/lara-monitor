<?php

namespace Nivseb\LaraMonitor\Struct\Tracing;

use Nivseb\LaraMonitor\Struct\Traits\CanGenerateId;

class StartTrace extends AbstractTrace
{
    use CanGenerateId;
    public readonly bool $sampled;
    public readonly float $sampleRate;

    protected readonly string $traceId;

    public function __construct(bool $sampled, float $sampleRate)
    {
        $this->id         = $this->generateId();
        $this->traceId    = $this->generateId(16);
        $this->sampled    = $sampled;
        $this->sampleRate = $sampleRate;
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function isSampled(): bool
    {
        return $this->sampled;
    }
}
