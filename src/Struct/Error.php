<?php

namespace Nivseb\LaraMonitor\Struct;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\Traits\CanGenerateId;
use Throwable;

class Error
{
    use CanGenerateId;

    public readonly string $id;

    public function __construct(
        public readonly AbstractChildTraceEvent $parentEvent,
        public string $type,
        public int|string $code,
        public string $message,
        public bool $handled,
        public CarbonInterface $time,
        public ?array $additionalData = null,
        public ?Throwable $throwable = null
    ) {
        $this->id = $this->generateId(8);
        $parentEvent->addError($this);
    }
}
