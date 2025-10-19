<?php

namespace Nivseb\LaraMonitor\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Redis\Events\CommandExecuted;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\User;
use Psr\Http\Message\RequestInterface;

interface MapperContract
{
    public function buildUserFromAuthenticated(string $guard, Authenticatable $user): ?User;

    public function buildPlainSpan(
        AbstractChildTraceEvent $parentTraceEvent,
        string $name,
        string $type,
        ?string $subType,
        CarbonInterface $startAt
    ): ?AbstractSpan;

    public function buildSystemSpan(
        AbstractChildTraceEvent $parentTraceEvent,
        string $name,
        string $type,
        ?string $subType,
        CarbonInterface $startAt
    ): ?AbstractSpan;

    public function buildHttpSpanFromRequest(
        AbstractChildTraceEvent $parentTraceEvent,
        RequestInterface $request,
        CarbonInterface $startAt
    ): ?AbstractSpan;

    public function buildRenderSpanForResponse(
        AbstractChildTraceEvent $parentTraceEvent,
        mixed $response,
        CarbonInterface $startAt
    ): ?AbstractSpan;

    public function buildQuerySpanFromExecuteEvent(
        AbstractChildTraceEvent $parentTraceEvent,
        QueryExecuted $event,
        CarbonInterface $finishAt
    ): ?AbstractSpan;

    public function buildRedisSpanFromExecuteEvent(
        AbstractChildTraceEvent $parentTraceEvent,
        CommandExecuted $event,
        CarbonInterface $finishAt
    ): ?AbstractSpan;

    public function buildJobQueueingSpan(
        AbstractChildTraceEvent $parentTraceEvent,
        JobQueueing $event,
        CarbonInterface $startAt
    ): ?AbstractSpan;
}
