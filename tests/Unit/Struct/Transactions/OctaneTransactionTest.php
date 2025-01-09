<?php

namespace Tests\Unit\Struct\Transactions;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\OctaneRequestTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;
use Nivseb\LaraMonitor\Struct\User;

test(
    'transaction name is build from method and path for default request transaction',
    function (string $method, string $uri, string $expectedName): void {
        $transaction         = new OctaneRequestTransaction(new StartTrace(false, 0.00));
        $transaction->method = $method;
        $transaction->path   = $uri;

        expect($transaction->getName())->toBe($expectedName);
    }
)
    ->with('simple method and path combinations');

test(
    'can set user to transaction',
    function (): void {
        $transaction = new OctaneRequestTransaction(new StartTrace(false, 0.00));
        $user        = new User();
        $transaction->setUser($user);
        expect($transaction->getUser())->toBe($user);
    }
);

test(
    'can unset user to transaction',
    function (): void {
        $transaction = new OctaneRequestTransaction(new StartTrace(false, 0.00));
        $user        = new User();
        $transaction->setUser($user);
        $transaction->setUser(null);
        expect($transaction->getUser())->toBeNull();
    }
);

test(
    'generate trace event id that match W3C requirement',
    function (): void {
        $transaction = new OctaneRequestTransaction(new StartTrace(false, 0.00));
        expect($transaction->id)
            ->toMatch('/^[a-f0-9]{16}$/')
            ->toBe($transaction->getId());
    }
);

test(
    'getTrace return parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new OctaneRequestTransaction($parent);
        expect($transaction->getTrace())->toBe($parent);
    }
);

test(
    'getTraceId return trace id from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new OctaneRequestTransaction($parent);
        expect($transaction->getTraceId())->toBe($parent->getTraceId());
    }
);

test(
    'isSampled return sampled flag from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new OctaneRequestTransaction($parent);
        expect($transaction->isSampled())->toBe($parent->isSampled());
    }
);

test(
    'determined isCompleted flag with start and finish time',
    function (?CarbonInterface $startTime, ?CarbonInterface $endTime, bool $expectedCompleted): void {
        $transaction           = new OctaneRequestTransaction(new StartTrace(false, 0.00));
        $transaction->startAt  = $startTime;
        $transaction->finishAt = $endTime;
        expect($transaction->isCompleted())->toBe($expectedCompleted);
    }
)
    ->with('possible values for completed detection');

test(
    'generate w3c trace parent with correct feature flag for sampled span',
    function (): void {
        $parent = new StartTrace(true, 0.00);
        $transaction = new OctaneRequestTransaction($parent);

        expect($transaction->asW3CTraceParent()->traceFlags)->toBe('01');
    }
);


test(
    'generate w3c trace parent with correct feature flag for unsampled span',
    function (): void {
        $parent = new StartTrace(false, 0.00);
        $transaction = new OctaneRequestTransaction($parent);

        expect($transaction->asW3CTraceParent()->traceFlags)->toBe('00');
    }
);
