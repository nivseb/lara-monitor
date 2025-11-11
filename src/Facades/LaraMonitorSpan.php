<?php

namespace Nivseb\LaraMonitor\Facades;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Facade;
use Nivseb\LaraMonitor\Contracts\Collector\SpanCollectorContract;
use Nivseb\LaraMonitor\Struct\AbstractTraceEvent;
use Nivseb\LaraMonitor\Struct\Error;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * @method static AbstractSpan|null       captureAction(string $name, string $type, Closure $callback, ?string $subType = null, bool $system = false)
 * @method static AbstractSpan|null       startAction(string $name, string $type, ?string $subType = null, ?CarbonInterface $startAt = null, bool $system = false)
 * @method static AbstractSpan|null       startHttpAction(RequestInterface $request, ?CarbonInterface $startAt = null)
 * @method static AbstractSpan|null       startRenderAction(mixed $response, ?CarbonInterface $startAt = null)
 * @method static AbstractSpan|null       startQueueingAction()
 * @method static AbstractTraceEvent|null stopAction(?CarbonInterface $finishAt = null)
 * @method static AbstractSpan|null       trackDatabaseQuery(QueryExecuted $event, ?CarbonInterface $finishAt = null)
 * @method static AbstractSpan|null       trackRedisCommand(CommandExecuted $event, ?CarbonInterface $finishAt = null)
 * @method static Error|null              captureExceptionAsError(Throwable $exception, bool $handled = false, CarbonInterface $time = null)
 * @method static Error|null              captureError(string $type, int|string $code, string $message, bool $handled = false, ?CarbonInterface $time = null, ?Throwable $exception = null, ?AbstractSpan &$span = null)
 *
 * @see SpanCollectorContract
 */
class LaraMonitorSpan extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SpanCollectorContract::class;
    }
}
