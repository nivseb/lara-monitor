<?php

namespace Tests\Unit\Struct;

use Carbon\Carbon;
use Closure;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Error;

test(
    'generate correct trace id',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $error      = new Error($traceEvent, 'TestError', 'T001', 'Error Message', true, Carbon::now());
        expect($error->id)
            ->toHaveLength(16)
            ->toMatch('/[\da-f]{16}/');
    }
)
    ->with('all possible child trace events');

test(
    'create random ids',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $error1     = new Error($traceEvent, 'TestError', 'T001', 'Error Message', true, Carbon::now());
        $error2     = new Error($traceEvent, 'TestError', 'T001', 'Error Message', true, Carbon::now());
        $error3     = new Error($traceEvent, 'TestError', 'T001', 'Error Message', true, Carbon::now());
        $error4     = new Error($traceEvent, 'TestError', 'T001', 'Error Message', true, Carbon::now());

        expect($error1->id)
            ->not()->toBe($error2)
            ->not()->toBe($error3)
            ->not()->toBe($error4)
            ->and($error2->id)
            ->not()->toBe($error3)
            ->not()->toBe($error4)
            ->and($error3->id)
            ->not()->toBe($error4);
    }
)
    ->with('all possible child trace events');

test(
    'error is added to parent trace child event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $error      = new Error($traceEvent, 'TestError', 'T001', 'Error Message', true, Carbon::now());

        expect($traceEvent->getErrors())
            ->toHaveCount(1)
            ->toBe([$error]);
    }
)
    ->with('all possible child trace events');

test(
    'add second error extend existing errors on trace child event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $error1     = new Error($traceEvent, 'TestError', 'T001', 'Error Message', true, Carbon::now());
        $error2     = new Error($traceEvent, 'TestError', 'T001', 'Error Message', true, Carbon::now());

        expect($traceEvent->getErrors())
            ->toHaveCount(2)
            ->toBe([$error1, $error2]);
    }
)
    ->with('all possible child trace events');

test(
    'parent starts with empty error bag',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        expect($traceEvent->hasErrors())
            ->toBeFalse()
            ->and($traceEvent->getErrors())
            ->toBe([]);
    }
)
    ->with('all possible child trace events');
