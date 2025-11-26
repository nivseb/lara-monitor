<?php

namespace Tests\Unit\Struct\Spans;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use GuzzleHttp\Psr7\Uri;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

test(
    'http span name is method with path',
    function (string $method, string $url, string $expectedName): void {
        $span = new HttpSpan(
            $method,
            new Uri($url),
            new RequestTransaction(new StartTrace(false, 0.0)),
            (int) Carbon::now()->format('Uu'),
        );

        expect($span->getName())->toBe($expectedName);
    }
)
    ->with('simple method and path combinations');

test(
    'generate trace event id that match W3C requirement',
    function (): void {
        $span = new HttpSpan(
            'GET',
            new Uri('/'),
            new RequestTransaction(new StartTrace(false, 0.0)),
            (int) Carbon::now()->format('Uu'),
        );
        expect($span->getId())
            ->toMatch('/^[a-f0-9]{16}$/');
    }
);

test(
    'getTrace return parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new HttpSpan(
            'GET',
            new Uri('/'),
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
        $span        = new HttpSpan(
            'GET',
            new Uri('/'),
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
        $span        = new HttpSpan(
            'GET',
            new Uri('/'),
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
        $span        = new HttpSpan(
            'GET',
            new Uri('/'),
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
        $span        = new HttpSpan(
            'GET',
            new Uri('/'),
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
        $span        = new HttpSpan(
            'GET',
            new Uri('/'),
            $transaction,
            (int) Carbon::now()->format('Uu'),
        );

        expect($span->asW3CTraceParent()->traceFlags)->toBe('00');
    }
);
