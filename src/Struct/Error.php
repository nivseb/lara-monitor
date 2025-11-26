<?php

namespace Nivseb\LaraMonitor\Struct;

use Nivseb\LaraMonitor\Struct\Traits\CanGenerateId;
use Nivseb\LaraMonitor\Struct\Traits\HasCustomContext;
use Nivseb\LaraMonitor\Struct\Traits\HasLabelContext;
use Throwable;

class Error
{
    use CanGenerateId;
    use HasCustomContext;
    use HasLabelContext;

    public function __construct(
        public readonly AbstractChildTraceEvent $parentEvent,
        public string $type,
        public int|string $code,
        public string $message,
        public bool $handled,
        public int $time,
        public ?Throwable $throwable = null
    ) {
        $this->id = $this->generateId();
        $parentEvent->addError($this);
    }
}
