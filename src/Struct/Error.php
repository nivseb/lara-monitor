<?php

namespace Nivseb\LaraMonitor\Struct;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\Traits\CanGenerateId;
use Nivseb\LaraMonitor\Struct\Traits\HasCustomContext;
use Nivseb\LaraMonitor\Struct\Traits\HasLabelContext;
use Throwable;

class Error
{
    use CanGenerateId;
    use HasCustomContext;
    use HasLabelContext;

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
