<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;

class RedisCommandSpan extends AbstractSpan
{
    public array $parameters      = [];
    public string $connectionName = '';
    public string $host           = '';
    public ?int $port             = null;

    public function __construct(
        public string $command,
        public string $statement,
        AbstractChildTraceEvent $parentEvent,
        int $startAt,
        int $finishAt,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return $this->command;
    }
}
