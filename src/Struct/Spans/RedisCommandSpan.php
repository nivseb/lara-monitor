<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;

class RedisCommandSpan extends AbstractSpan
{
    public array $parameters      = [];
    public string $connectionName = '';
    public string $host           = '';
    public ?int $port;

    public function __construct(
        public string $command,
        public string $statement,
        AbstractChildTraceEvent $parentEvent,
        CarbonInterface $startAt,
        CarbonInterface $finishAt,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return $this->command;
    }
}
