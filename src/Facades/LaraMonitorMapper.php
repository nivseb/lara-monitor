<?php

namespace Nivseb\LaraMonitor\Facades;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Facade;
use Nivseb\LaraMonitor\Contracts\MapperContract;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\User;
use Psr\Http\Message\RequestInterface;

/**
 * @method static AbstractSpan|null buildPlainSpan(AbstractChildTraceEvent $parentTraceEvent,string $name,string $type,null|string $subType,CarbonInterface $startAt)
 * @method static AbstractSpan|null buildSystemSpan(AbstractChildTraceEvent $parentTraceEvent,string $name,string $type,null|string $subType,CarbonInterface $startAt)
 * @method static AbstractSpan|null buildHttpSpanFromRequest(AbstractChildTraceEvent $parentTraceEvent,RequestInterface $request,CarbonInterface $startAt)
 * @method static AbstractSpan|null buildRenderSpanForResponse(AbstractChildTraceEvent $parentTraceEvent,mixed $response,CarbonInterface $startAt)
 * @method static AbstractSpan|null buildQuerySpanFromExecuteEvent(AbstractChildTraceEvent $parentTraceEvent,QueryExecuted $event,CarbonInterface $finishAt)
 * @method static AbstractSpan|null buildRedisSpanFromExecuteEvent(AbstractChildTraceEvent $parentTraceEvent,CommandExecuted $event,CarbonInterface $finishAt)
 * @method static User|null         buildUserFromAuthenticated(string $guard, Authenticatable $user)
 *
 * @see MapperContract
 */
class LaraMonitorMapper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MapperContract::class;
    }
}
