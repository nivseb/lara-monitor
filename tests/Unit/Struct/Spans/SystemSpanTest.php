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
            Carbon::now(),
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
            Carbon::now(),
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
            Carbon::now(),
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
            Carbon::now(),
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
            Carbon::now(),
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
            Carbon::now(),
        );
        $span->startAt  = $startTime;
        $span->finishAt = $endTime;
        expect($span->isCompleted())->toBe($expectedCompleted);
    }
)
    ->with('possible values for completed detection');