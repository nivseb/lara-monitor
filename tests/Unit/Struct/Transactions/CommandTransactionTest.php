<?php

namespace Tests\Unit\Struct\Transactions;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\User;

test(
    'command is used as transaction name',
    function (): void {
        $expectedName         = fake()->regexify('\w{30}');
        $transaction          = new CommandTransaction(new StartTrace(false, 0.00));
        $transaction->command = $expectedName;

        expect($transaction->getName())->toBe($expectedName);
    }
);

test(
    'can set user to transaction',
    function (): void {
        $transaction = new CommandTransaction(new StartTrace(false, 0.00));
        $user        = new User();
        $transaction->setUser($user);
        expect($transaction->getUser())->toBe($user);
    }
);

test(
    'can unset user to transaction',
    function (): void {
        $transaction = new CommandTransaction(new StartTrace(false, 0.00));
        $user        = new User();
        $transaction->setUser($user);
        $transaction->setUser(null);
        expect($transaction->getUser())->toBeNull();
    }
);

test(
    'generate trace event id that match W3C requirement',
    function (): void {
        $transaction = new CommandTransaction(new StartTrace(false, 0.00));
        expect($transaction->id)
            ->toMatch('/^[a-f0-9]{16}$/')
            ->toBe($transaction->getId());
    }
);

test(
    'getTrace return parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new CommandTransaction($parent);
        expect($transaction->getTrace())->toBe($parent);
    }
);

test(
    'getTraceId return trace id from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new CommandTransaction($parent);
        expect($transaction->getTraceId())->toBe($parent->getTraceId());
    }
);

test(
    'isSampled return sampled flag from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new CommandTransaction($parent);
        expect($transaction->isSampled())->toBe($parent->isSampled());
    }
);

test(
    'determined isCompleted flag with start and finish time',
    function (?CarbonInterface $startTime, ?CarbonInterface $endTime, bool $expectedCompleted): void {
        $parent                = new StartTrace(false, 0.00);
        $transaction           = new CommandTransaction($parent);
        $transaction->startAt  = $startTime ? (int) $startTime->format('Uu') : null;
        $transaction->finishAt = $endTime ? (int) $endTime->format('Uu') : null;
        expect($transaction->isCompleted())->toBe($expectedCompleted);
    }
)
    ->with('possible values for completed detection');

test(
    'generate w3c trace parent with correct feature flag for sampled span',
    function (): void {
        $parent      = new StartTrace(true, 0.00);
        $transaction = new CommandTransaction($parent);

        expect($transaction->asW3CTraceParent()->traceFlags)->toBe('01');
    }
);

test(
    'generate w3c trace parent with correct feature flag for unsampled span',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new CommandTransaction($parent);

        expect($transaction->asW3CTraceParent()->traceFlags)->toBe('00');
    }
);
