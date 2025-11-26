<?php

namespace Tests\Component\Services\Mapper;

use Carbon\Carbon;
use Closure;
use Nivseb\LaraMonitor\Services\Mapper;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\PlainSpan;

test(
    'span is build as plain span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $mapper     = new Mapper();
        $span       = $mapper->buildPlainSpan(
            $traceEvent,
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            Carbon::now()
        );

        expect($span)->toBeInstanceOf(PlainSpan::class);
    }
)
    ->with('all possible child trace events');

test(
    'span get correct trace parent',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $mapper     = new Mapper();

        /** @var PlainSpan $span */
        $span = $mapper->buildPlainSpan(
            $traceEvent,
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            Carbon::now()
        );

        expect($span->parentEvent)->toBe($traceEvent);
    }
)
    ->with('all possible child trace events');

test(
    'span receive given name',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent   = $buildTraceChild();
        $expectedName = fake()->regexify('\w{10}');

        $mapper = new Mapper();

        /** @var PlainSpan $span */
        $span = $mapper->buildPlainSpan(
            $traceEvent,
            $expectedName,
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            Carbon::now()
        );

        expect($span->name)->toBe($expectedName);
    }
)
    ->with('all possible child trace events');

test(
    'span receive given type',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent   = $buildTraceChild();
        $expectedType = fake()->regexify('\w{10}');

        $mapper = new Mapper();

        /** @var PlainSpan $span */
        $span = $mapper->buildPlainSpan(
            $traceEvent,
            fake()->regexify('\w{10}'),
            $expectedType,
            fake()->regexify('\w{10}'),
            Carbon::now()
        );

        expect($span->type)->toBe($expectedType);
    }
)
    ->with('all possible child trace events');

test(
    'span receive given sub type',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent      = $buildTraceChild();
        $expectedSubType = fake()->regexify('\w{10}');

        $mapper = new Mapper();

        /** @var PlainSpan $span */
        $span = $mapper->buildPlainSpan(
            $traceEvent,
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            $expectedSubType,
            Carbon::now()
        );

        expect($span->subType)->toBe($expectedSubType);
    }
)
    ->with('all possible child trace events');

test(
    'span receive given date as start time',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $date       = new Carbon(fake()->dateTime());
        $time       = (int) $date->format('Uu');

        $mapper = new Mapper();

        /** @var PlainSpan $span */
        $span = $mapper->buildPlainSpan(
            $traceEvent,
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            $date
        );

        expect($span->startAt)->toBe($time);
    }
)
    ->with('all possible child trace events');
