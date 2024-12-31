<?php

namespace Tests\Component\Services\Mapper;

use Carbon\Carbon;
use Nivseb\LaraMonitor\Services\Mapper;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;

test(
    'span is build as plain span',
    function (AbstractChildTraceEvent $traceEvent): void {
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
    function (AbstractChildTraceEvent $traceEvent): void {
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
    function (AbstractChildTraceEvent $traceEvent): void {
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
    function (AbstractChildTraceEvent $traceEvent): void {
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
    function (AbstractChildTraceEvent $traceEvent): void {
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
    function (AbstractChildTraceEvent $traceEvent): void {
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
