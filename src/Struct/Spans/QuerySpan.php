<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;

class QuerySpan extends AbstractSpan
{
    public array $bindings        = [];
    public string $host           = '';
    public string $connectionName = '';
    public string $sqlStatement   = '';
    public ?int $port             = null;
    public string $databaseType   = '';

    public function __construct(
        public string $queryType,
        public array $tables,
        AbstractChildTraceEvent $parentEvent,
        CarbonInterface $startAt,
        CarbonInterface $finishAt,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return $this->queryType.' '.implode(',', $this->tables);
    }
}
