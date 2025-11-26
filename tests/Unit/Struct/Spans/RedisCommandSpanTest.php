<?php

namespace Tests\Unit\Struct\Spans;

use Carbon\Carbon;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

test(
    'redis span name is command',
    function (): void {
        $command = fake()->regexify('\w{30}');
        $span    = new RedisCommandSpan(
            $command,
            fake()->regexify('\w{100}'),
            new RequestTransaction(new StartTrace(false, 0.0)),
            (int) Carbon::now()->format('Uu'),
            (int) Carbon::now()->format('Uu')
        );

        expect($span->getName())->toBe($command);
    }
);

test(
    'generate trace event id that match W3C requirement',
    function (): void {
        $span = new RedisCommandSpan(
            fake()->regexify('\w{100}'),
            fake()->regexify('\w{100}'),
            new RequestTransaction(new StartTrace(false, 0.0)),
            (int) Carbon::now()->format('Uu'),
            (int) Carbon::now()->format('Uu')
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
        $span        = new RedisCommandSpan(
            fake()->regexify('\w{100}'),
            fake()->regexify('\w{100}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
            (int) Carbon::now()->format('Uu')
        );
        expect($span->getTrace())->toBe($parent);
    }
);

test(
    'getTraceId return trace id from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new RedisCommandSpan(
            fake()->regexify('\w{100}'),
            fake()->regexify('\w{100}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
            (int) Carbon::now()->format('Uu')
        );
        expect($span->getTraceId())->toBe($parent->getTraceId());
    }
);

test(
    'isSampled return sampled flag from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new RedisCommandSpan(
            fake()->regexify('\w{100}'),
            fake()->regexify('\w{100}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
            (int) Carbon::now()->format('Uu')
        );
        expect($span->isSampled())->toBe($parent->isSampled());
    }
);

test(
    'determined isCompleted flag with start and finish time',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new RedisCommandSpan(
            fake()->regexify('\w{100}'),
            fake()->regexify('\w{100}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
            (int) Carbon::now()->format('Uu')
        );

        expect($span->isCompleted())->toBeTrue();
    }
);

test(
    'generate w3c trace parent with correct feature flag for sampled span',
    function (): void {
        $parent      = new StartTrace(true, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new RedisCommandSpan(
            fake()->regexify('\w{100}'),
            fake()->regexify('\w{100}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
            (int) Carbon::now()->format('Uu')
        );

        expect($span->asW3CTraceParent()->traceFlags)->toBe('01');
    }
);

test(
    'generate w3c trace parent with correct feature flag for unsampled span',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new RedisCommandSpan(
            fake()->regexify('\w{100}'),
            fake()->regexify('\w{100}'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
            (int) Carbon::now()->format('Uu')
        );

        expect($span->asW3CTraceParent()->traceFlags)->toBe('00');
    }
);
