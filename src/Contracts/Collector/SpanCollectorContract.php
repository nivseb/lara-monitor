<?php

namespace Nivseb\LaraMonitor\Contracts\Collector;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Redis\Events\CommandExecuted;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Struct\AbstractTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * @see LaraMonitorSpan
 */
interface SpanCollectorContract
{
    /**
     * @throws Throwable
     */
    public function captureAction(
        string $name,
        string $type,
        Closure $callback,
        ?string $subType = null,
        bool $system = false,
    ): ?AbstractSpan;

    public function startAction(
        string $name,
        string $type,
        ?string $subType = null,
        ?CarbonInterface $startAt = null,
        bool $system = false
    ): ?AbstractSpan;

    public function startHttpAction(RequestInterface $request, ?CarbonInterface $startAt = null): ?AbstractSpan;

    public function startRenderAction(mixed $response, ?CarbonInterface $startAt = null): ?AbstractSpan;

    public function startQueueingAction(JobQueueing $event, ?CarbonInterface $startAt = null): ?AbstractSpan;

    /**
     * stops the current trace event and return it.
     */
    public function stopAction(?CarbonInterface $finishAt = null): ?AbstractTraceEvent;

    public function trackDatabaseQuery(QueryExecuted $event, ?CarbonInterface $finishAt = null): ?AbstractSpan;

    public function trackRedisCommand(CommandExecuted $event, ?CarbonInterface $finishAt = null): ?AbstractSpan;
}
