<?php

namespace Nivseb\LaraMonitor\Contracts\Collector;

use Carbon\CarbonInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Redis\Events\CommandExecuted;
use Nivseb\LaraMonitor\Struct\AbstractTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Psr\Http\Message\RequestInterface;

interface SpanCollectorContract
{
    public function startAction(
        string $name,
        string $type,
        ?string $subType = null,
        ?CarbonInterface $startAt = null,
        bool $system = false
    ): ?AbstractSpan;

    public function startHttpAction(RequestInterface $request, ?CarbonInterface $startAt = null): ?AbstractSpan;

    public function startRenderAction(mixed $response, ?CarbonInterface $startAt = null): ?AbstractSpan;

    public function stopAction(?CarbonInterface $finishAt = null): ?AbstractTraceEvent;

    public function trackDatabaseQuery(QueryExecuted $event, ?CarbonInterface $finishAt = null): ?AbstractSpan;

    public function trackRedisCommand(CommandExecuted $event, ?CarbonInterface $finishAt = null): ?AbstractSpan;
}
