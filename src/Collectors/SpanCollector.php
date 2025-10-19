<?php

namespace Nivseb\LaraMonitor\Collectors;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Redis\Events\CommandExecuted;
use Nivseb\LaraMonitor\Contracts\Collector\SpanCollectorContract;
use Nivseb\LaraMonitor\Facades\LaraMonitorError;
use Nivseb\LaraMonitor\Facades\LaraMonitorMapper;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\AbstractTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Traits\HasLogging;
use Psr\Http\Message\RequestInterface;
use Throwable;

class SpanCollector implements SpanCollectorContract
{
    use HasLogging;

    public function startAction(
        string $name,
        string $type,
        ?string $subType = null,
        ?CarbonInterface $startAt = null,
        bool $system = false
    ): ?AbstractSpan {
        try {
            $startAt ??= Carbon::now();
            $parentTraceEvent = LaraMonitorStore::getCurrentTraceEvent();
            if (!$parentTraceEvent || $parentTraceEvent->isCompleted()) {
                return null;
            }
            $span = $system
                ? LaraMonitorMapper::buildSystemSpan($parentTraceEvent, $name, $type, $subType, $startAt)
                : LaraMonitorMapper::buildPlainSpan($parentTraceEvent, $name, $type, $subType, $startAt);
            if (!$span) {
                return null;
            }
            LaraMonitorStore::addSpan($span);

            return $span;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start action!', $exception);

            return null;
        }
    }

    public function startHttpAction(RequestInterface $request, ?CarbonInterface $startAt = null): ?AbstractSpan
    {
        try {
            $parentTraceEvent = LaraMonitorStore::getCurrentTraceEvent();
            if (!$parentTraceEvent || $parentTraceEvent->isCompleted()) {
                return null;
            }
            $span = LaraMonitorMapper::buildHttpSpanFromRequest($parentTraceEvent, $request, $startAt ?? Carbon::now());
            if (!$span) {
                return null;
            }
            LaraMonitorStore::addSpan($span);

            return $span;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start http action!', $exception);

            return null;
        }
    }

    public function startRenderAction(mixed $response, ?CarbonInterface $startAt = null): ?AbstractSpan
    {
        try {
            $parentTraceEvent = LaraMonitorStore::getCurrentTraceEvent();
            if (!$parentTraceEvent || $parentTraceEvent->isCompleted()) {
                return null;
            }
            $span = LaraMonitorMapper::buildRenderSpanForResponse(
                $parentTraceEvent,
                $response,
                $startAt ?? Carbon::now()
            );
            if (!$span) {
                return null;
            }
            LaraMonitorStore::addSpan($span);

            return $span;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t render action!', $exception);

            return null;
        }
    }

    public function stopAction(?CarbonInterface $finishAt = null): ?AbstractTraceEvent
    {
        try {
            $currentTraceEvent = LaraMonitorStore::getCurrentTraceEvent();
            if (!$currentTraceEvent instanceof AbstractSpan) {
                return null;
            }
            $currentTraceEvent->finishAt = $finishAt ?? Carbon::now();
            LaraMonitorStore::setCurrentTraceEvent($currentTraceEvent->parentEvent);

            return $currentTraceEvent->parentEvent;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t stop action!', $exception);

            return null;
        }
    }

    public function trackDatabaseQuery(QueryExecuted $event, ?CarbonInterface $finishAt = null): ?AbstractSpan
    {
        try {
            $parentTraceEvent = LaraMonitorStore::getCurrentTraceEvent();
            if (!$parentTraceEvent || $parentTraceEvent->isCompleted()) {
                return null;
            }

            $querySpan = LaraMonitorMapper::buildQuerySpanFromExecuteEvent(
                $parentTraceEvent,
                $event,
                $finishAt ?? Carbon::now()
            );
            if (!$querySpan) {
                return null;
            }
            LaraMonitorStore::addSpan($querySpan);

            return $querySpan;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t track database query!', $exception);

            return null;
        }
    }

    public function trackRedisCommand(CommandExecuted $event, ?CarbonInterface $finishAt = null): ?AbstractSpan
    {
        try {
            $parentTraceEvent = LaraMonitorStore::getCurrentTraceEvent();
            if (!$parentTraceEvent || $parentTraceEvent->isCompleted()) {
                return null;
            }

            $commandSpan = LaraMonitorMapper::buildRedisSpanFromExecuteEvent(
                $parentTraceEvent,
                $event,
                $finishAt ?? Carbon::now()
            );
            if (!$commandSpan) {
                return null;
            }
            LaraMonitorStore::addSpan($commandSpan);

            return $commandSpan;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t track redis command!', $exception);

            return null;
        }
    }

    /**
     * @throws Throwable
     */
    public function captureAction(string $name, string $type, Closure $callback, ?string $subType = null, bool $system = false): ?AbstractSpan
    {
        $span = $this->startAction($name, $type, $subType, Carbon::now(), $system);

        try {
            $callback();
        } catch (Throwable $exception) {
            if ($span) {
                $span->finishAt   = Carbon::now();
                $span->successful = false;
                LaraMonitorError::captureExceptionAsError($exception);
            }

            throw $exception;
        }

        if ($span) {
            $span->finishAt   = Carbon::now();
            $span->successful = true;
        }

        return $span;
    }

    public function startQueueingAction(JobQueueing $event, ?CarbonInterface $startAt = null): ?AbstractSpan
    {
        try {
            $parentTraceEvent = LaraMonitorStore::getCurrentTraceEvent();
            if (!$parentTraceEvent || $parentTraceEvent->isCompleted()) {
                return null;
            }
            $span = LaraMonitorMapper::buildJobQueueingSpan(
                $parentTraceEvent,
                $event,
                $startAt ?? Carbon::now()
            );
            if (!$span) {
                return null;
            }
            LaraMonitorStore::addSpan($span);

            return $span;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start queueing action!', $exception);

            return null;
        }
    }
}
