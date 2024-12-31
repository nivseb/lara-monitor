<?php

namespace Tests\Unit\Struct\Tracing;

use Nivseb\LaraMonitor\Struct\AbstractTraceEvent;
use Nivseb\LaraMonitor\Struct\Tracing\ExternalTrace;
use Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent;

test(
    'map w3c trace correct',
    function (W3CTraceParent $w3cTrace): void {
        $trace = new ExternalTrace($w3cTrace);
        expect($trace->getTrace())
            ->toBe($trace)
            ->and($trace->getTraceId())
            ->toBe($w3cTrace->traceId)
            ->and($trace->getId())
            ->toBe($w3cTrace->parentId)
            ->and($trace->isSampled())
            ->toBe($w3cTrace->sampled());
    }
)
    ->with('w3c parents');

test(
    'generate correct W3C trace parent for trace event',
    function (AbstractTraceEvent $traceEvent): void {
        $w3cTrace = $traceEvent->asW3CTraceParent();
        expect($w3cTrace)
            ->toBeInstanceOf(W3CTraceParent::class)
            ->and($w3cTrace->traceId)
            ->toBe($traceEvent->getTraceId())
            ->and($w3cTrace->parentId)
            ->toBe($traceEvent->getId())
            ->and($w3cTrace->sampled())
            ->toBe($traceEvent->isSampled());
    }
)
    ->with('external parents');
