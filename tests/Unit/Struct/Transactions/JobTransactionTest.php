<?php

namespace Tests\Unit\Struct\Transactions;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Nivseb\LaraMonitor\Struct\User;

test(
    'job name is used as transaction name',
    function (): void {
        $expectedName         = fake()->regexify('\w{30}');
        $transaction          = new JobTransaction(new StartTrace(false, 0.00));
        $transaction->jobName = $expectedName;

        expect($transaction->getName())->toBe($expectedName);
    }
);

test(
    'can set user to transaction',
    function (): void {
        $transaction = new JobTransaction(new StartTrace(false, 0.00));
        $user        = new User();
        $transaction->setUser($user);
        expect($transaction->getUser())->toBe($user);
    }
);

test(
    'can unset user to transaction',
    function (): void {
        $transaction = new JobTransaction(new StartTrace(false, 0.00));
        $user        = new User();
        $transaction->setUser($user);
        $transaction->setUser(null);
        expect($transaction->getUser())->toBeNull();
    }
);

test(
    'generate trace event id that match W3C requirement',
    function (): void {
        $transaction = new JobTransaction(new StartTrace(false, 0.00));
        expect($transaction->id)
            ->toMatch('/^[a-f0-9]{16}$/')
            ->toBe($transaction->getId());
    }
);

test(
    'getTrace return parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new JobTransaction($parent);
        expect($transaction->getTrace())->toBe($parent);
    }
);

test(
    'getTraceId return trace id from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new JobTransaction($parent);
        expect($transaction->getTraceId())->toBe($parent->getTraceId());
    }
);

test(
    'isSampled return sampled flag from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new JobTransaction($parent);
        expect($transaction->isSampled())->toBe($parent->isSampled());
    }
);

test(
    'determined isCompleted flag with start and finish time',
    function (?CarbonInterface $startTime, ?CarbonInterface $endTime, bool $expectedCompleted): void {
        $transaction           = new JobTransaction(new StartTrace(false, 0.00));
        $transaction->startAt  = $startTime;
        $transaction->finishAt = $endTime;
        expect($transaction->isCompleted())->toBe($expectedCompleted);
    }
)
    ->with('possible values for completed detection');
