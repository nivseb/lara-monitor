<?php

namespace Tests\Unit\Struct\Spans;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

test(
    'span name is -',
    function (): void {
        $expectedName = fake()->regexify('\w{10}');
        $span         = new SystemSpan(
            $expectedName,
            fake()->regexify('\w{10}'),
            new RequestTransaction(new StartTrace(false, 0.0)),
            (int) Carbon::now()->format('Uu'),
        );

        expect($span->getName())->toBe($expectedName);
    }
);

test(
    'generate trace event id that match W3C requirement',
    function (): void {
        $span = new SystemSpan(
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            new RequestTransaction(new StartTrace(false, 0.0)),
            (int) Carbon::now()->format('Uu'),
        );
        expect($span->id)
            ->toMatch('/^[a-f0-9]{16}$/')
            ->toBe($span->getId());
    }
);

test(
    'getTrace return parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new SystemSpan(
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
        );
        expect($span->getTrace())->toBe($parent);
    }
);

test(
    'getTraceId return trace id from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new SystemSpan(
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
        );
        expect($span->getTraceId())->toBe($parent->getTraceId());
    }
);

test(
    'isSampled return sampled flag from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new SystemSpan(
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
        );
        expect($span->isSampled())->toBe($parent->isSampled());
    }
);

test(
    'determined isCompleted flag with start and finish time',
    function (?CarbonInterface $startTime, ?CarbonInterface $endTime, bool $expectedCompleted): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new SystemSpan(
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
        );
        $span->startAt  = $startTime ? (int) $startTime->format('Uu') : null;
        $span->finishAt = $endTime ? (int) $endTime->format('Uu') : null;
        expect($span->isCompleted())->toBe($expectedCompleted);
    }
)
    ->with('possible values for completed detection');

test(
    'generate w3c trace parent with correct feature flag for sampled span',
    function (): void {
        $parent      = new StartTrace(true, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new SystemSpan(
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
        );

        expect($span->asW3CTraceParent()->traceFlags)->toBe('01');
    }
);

test(
    'generate w3c trace parent with correct feature flag for unsampled span',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new SystemSpan(
            fake()->regexify('\w{10}'),
            fake()->regexify('\w{10}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
        );

        expect($span->asW3CTraceParent()->traceFlags)->toBe('00');
    }
);
