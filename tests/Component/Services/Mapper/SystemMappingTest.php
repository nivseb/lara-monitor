<?php

namespace Tests\Component\Services\Mapper;

use Carbon\Carbon;
use Nivseb\LaraMonitor\Services\Mapper;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;

test(
    'span is build as plain span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (\Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $mapper = new Mapper();
        $span   = $mapper->buildSystemSpan(
            $traceEvent,
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            Carbon::now()
        );

        expect($span)->toBeInstanceOf(SystemSpan::class);
    }
)
    ->with('all possible child trace events');

test(
    'span get correct trace parent',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (\Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $mapper = new Mapper();

        /** @var SystemSpan $span */
        $span = $mapper->buildSystemSpan(
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
    function (\Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $expectedName = fake()->regexify('\w{10}');

        $mapper = new Mapper();

        /** @var SystemSpan $span */
        $span = $mapper->buildSystemSpan(
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
    function (\Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $expectedType = fake()->regexify('\w{10}');

        $mapper = new Mapper();

        /** @var SystemSpan $span */
        $span = $mapper->buildSystemSpan(
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
    function (\Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $expectedSubType = fake()->regexify('\w{10}');

        $mapper = new Mapper();

        /** @var SystemSpan $span */
        $span = $mapper->buildSystemSpan(
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
    function (\Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $expectedDate = new Carbon(fake()->dateTime());

        $mapper = new Mapper();

        /** @var SystemSpan $span */
        $span = $mapper->buildSystemSpan(
            $traceEvent,
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            $expectedDate
        );

        expect($span->startAt)->toBe($expectedDate);
    }
)
    ->with('all possible child trace events');
