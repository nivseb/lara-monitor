<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Illuminate\Support\Arr;
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
        int $startAt,
        int $finishAt,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        $table = Arr::first($this->tables);
        if (in_array($this->queryType, ['SELECT', 'DELETE'])) {
            return $this->queryType.($table ? ' FROM ' : '').$table;
        }
        if ($this->queryType === 'INSERT') {
            return 'INSERT'.($table ? ' INTO ' : '').$table;
        }

        return trim($this->queryType.' '.$table);
    }
}
