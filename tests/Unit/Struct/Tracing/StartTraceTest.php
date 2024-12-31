<?php

namespace Tests\Unit\Struct\Tracing;

use Nivseb\LaraMonitor\Struct\AbstractTraceEvent;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent;

test(
    'generate correct id',
    function (): void {
        $trace = new StartTrace(false, 0.00);
        expect($trace->getId())
            ->toHaveLength(16)
            ->toMatch('/[\da-f]{16}/');
    }
);

test(
    'generate correct trace id',
    function (): void {
        $trace = new StartTrace(false, 0.00);
        expect($trace->getTraceId())
            ->toHaveLength(32)
            ->toMatch('/[\da-f]{32}/');
    }
);

test(
    'create random ids',
    function (): void {
        $trace1 = new StartTrace(false, 0.00);
        $trace2 = new StartTrace(false, 0.00);
        $trace3 = new StartTrace(false, 0.00);
        $trace4 = new StartTrace(false, 0.00);

        expect($trace1->getId())
            ->not()->toBe($trace2)
            ->not()->toBe($trace3)
            ->not()->toBe($trace4)
            ->and($trace2->getId())
            ->not()->toBe($trace3)
            ->not()->toBe($trace4)
            ->and($trace3->getId())
            ->not()->toBe($trace4);
    }
);

test(
    'create random trace ids',
    function (): void {
        $trace1 = new StartTrace(false, 0.00);
        $trace2 = new StartTrace(false, 0.00);
        $trace3 = new StartTrace(false, 0.00);
        $trace4 = new StartTrace(false, 0.00);

        expect($trace1->getTraceId())
            ->not()->toBe($trace2)
            ->not()->toBe($trace3)
            ->not()->toBe($trace4)
            ->and($trace2->getTraceId())
            ->not()->toBe($trace3)
            ->not()->toBe($trace4)
            ->and($trace3->getTraceId())
            ->not()->toBe($trace4);
    }
);

test(
    'getId return id',
    function (): void {
        $trace = new StartTrace(false, 0.00);
        expect($trace->getId())->toBe($trace->getId());
    }
);

test(
    'getTraceId return traceId',
    function (): void {
        $trace = new StartTrace(false, 0.00);
        expect($trace->getTraceId())->toBe($trace->getTraceId());
    }
);

test(
    'isSampled return given sampled flag',
    function (): void {
        $trace = new StartTrace(true, 0.00);
        expect($trace->isSampled())->toBeTrue();
        $trace = new StartTrace(false, 0.00);
        expect($trace->isSampled())->toBeFalse();
    }
);

test(
    'getTrace return same instance',
    function (): void {
        $trace = new StartTrace(false, 0.00);
        expect($trace->getTrace())->toBe($trace);
    }
);

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
    ->with('own trace events');
