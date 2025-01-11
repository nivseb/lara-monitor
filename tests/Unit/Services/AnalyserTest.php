<?php

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Services\Analyser;
use Nivseb\LaraMonitor\Struct\Error;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

test(
    'request is successful with response without errors',
    function (int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeTrue();
    }
)
    ->with('successful response codes for request transactions');

test(
    'request is unsuccessful with response without errors',
    function (int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeFalse();
    }
)
    ->with('unsuccessful response codes for request transactions');

test(
    'request is successful with response with handled error',
    function (int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;
        new Error($transaction, 'test', 'DummyCode', 'Message', true, Carbon::now());

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeTrue();
    }
)
    ->with('successful response codes for request transactions');

test(
    'request is unsuccessful with response with handled error',
    function (int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;
        new Error($transaction, 'test', 'DummyCode', 'Message', true, Carbon::now());

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeFalse();
    }
)
    ->with('unsuccessful response codes for request transactions');

test(
    'request is successful with response with unhandled error',
    function (int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;
        new Error($transaction, 'test', 'DummyCode', 'Message', false, Carbon::now());

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeTrue();
    }
)
    ->with('successful response codes for request transactions');

test(
    'request is unsuccessful with response with unhandled error',
    function (int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;
        new Error($transaction, 'test', 'DummyCode', 'Message', false, Carbon::now());

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeFalse();
    }
)
    ->with('unsuccessful response codes for request transactions');

test(
    'request depends on allowed status with response without errors',
    function (int $allowedStatusCode, int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), $allowedStatusCode);

        expect($transaction->successful)->toBe($allowedStatusCode === $responseCode);
    }
)
    ->with('response codes for request transactions')
    ->with('response codes for request transactions');

test(
    'request depends on allowed status with response with handled error',
    function (int $allowedStatusCode, int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;
        new Error($transaction, 'test', 'DummyCode', 'Message', true, Carbon::now());

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), $allowedStatusCode);

        expect($transaction->successful)->toBe($allowedStatusCode === $responseCode);
    }
)
    ->with('response codes for request transactions')
    ->with('response codes for request transactions');

test(
    'request is successful with response with unhandled error and allowed status is ignored',
    function (int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;
        new Error($transaction, 'test', 'DummyCode', 'Message', false, Carbon::now());

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), 500);

        expect($transaction->successful)->toBeTrue();
    }
)
    ->with('successful response codes for request transactions');

test(
    'request is unsuccessful with response with unhandled error and allowed status is ignored',
    function (int $responseCode): void {
        $transaction               = new RequestTransaction(new StartTrace(false, 0.0));
        $transaction->responseCode = $responseCode;
        new Error($transaction, 'test', 'DummyCode', 'Message', false, Carbon::now());

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), 200);

        expect($transaction->successful)->toBeFalse();
    }
)
    ->with('unsuccessful response codes for request transactions');

test(
    'job is successful if the job is not failed',
    function (): void {
        $transaction         = new JobTransaction(new StartTrace(false, 0.0));
        $transaction->failed = false;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeTrue();
    }
);

test(
    'job is unsuccessful if the job is failed',
    function (): void {
        $transaction         = new JobTransaction(new StartTrace(false, 0.0));
        $transaction->failed = true;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeFalse();
    }
);

test(
    'command is successful if the exit code is zero',
    function (): void {
        $transaction           = new CommandTransaction(new StartTrace(false, 0.0));
        $transaction->exitCode = 0;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeTrue();
    }
);

test(
    'command is unsuccessful if the code is not zero',
    function (int $exitCode): void {
        $transaction           = new CommandTransaction(new StartTrace(false, 0.0));
        $transaction->exitCode = $exitCode;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), null);

        expect($transaction->successful)->toBeFalse();
    }
)
    ->with('error exit codes');

test(
    'command is successful if the code is is same as allowed value',
    function (int $allowedExitCode, int $exitCode): void {
        $transaction           = new CommandTransaction(new StartTrace(false, 0.0));
        $transaction->exitCode = $exitCode;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection(), $allowedExitCode);

        expect($transaction->successful)->toBe($allowedExitCode === $exitCode);
    }
)
    ->with('error exit codes')
    ->with('error exit codes');

test(
    'http span is successful for response',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, int $responseCode): void {
        $transaction        = $buildTransaction();
        $span               = new HttpSpan('GET', new Uri('/'), $transaction, Carbon::now());
        $span->responseCode = $responseCode;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection([$span]), null);

        expect($span->successful)->toBeTrue();
    }
)
    ->with('all possible transaction types')
    ->with('successful response codes for http span');

test(
    'http span is unsuccessful for response',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, int $responseCode): void {
        $transaction        = $buildTransaction();
        $span               = new HttpSpan('GET', new Uri('/'), $transaction, Carbon::now());
        $span->responseCode = $responseCode;

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection([$span]), null);

        expect($span->successful)->toBeFalse();
    }
)
    ->with('all possible transaction types')
    ->with('unsuccessful response codes for http span');

test(
    'query span is every time successful',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();
        $span        = new QuerySpan(
            fake()->regexify('\w{10}'),
            [fake()->regexify('\w{10}')],
            $transaction,
            Carbon::now(),
            Carbon::now()
        );

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection([$span]), null);

        expect($span->successful)->toBeTrue();
    }
)
    ->with('all possible transaction types');

test(
    'redis command span is every time successful',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();
        $span        = new RedisCommandSpan('eval', 'statement', $transaction, Carbon::now(), Carbon::now());

        $analyser = new Analyser();
        $analyser->analyse($transaction, new Collection([$span]), null);

        expect($span->successful)->toBeTrue();
    }
)
    ->with('all possible transaction types');
