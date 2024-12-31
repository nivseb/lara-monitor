<?php

namespace Tests\Component\Elastic\Builder;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Elastic\Builder\TransactionBuilder;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Tracing\ExternalTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

test(
    'add wrapper for transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $duration              = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->word());

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('transaction');
    }
)
    ->with('all possible transaction types');

test(
    'dont build transaction record without duration',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturnNull();
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types');

test(
    'build transaction record duration that is zero',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn(0);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->word());

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    }
)
    ->with('all possible transaction types');

test(
    'dont build transaction record without start',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $duration              = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturnNull();
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->word());

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types');

test(
    'build span record if start that is zero',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $duration              = fake()->numberBetween(10000);
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn(0);
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->word());

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    }
)
    ->with('all possible transaction types');

test(
    'add correct base data for transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $duration              = fake()->numberBetween(10000);
        $type                  = fake()->word();
        $outcome               = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);
        $formaterMock->allows('getTransactionType')->andReturn($type);
        $formaterMock->allows('getOutcome')->andReturn($outcome);

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'id'                  => $transaction->id,
                    'type'                => $type,
                    'name'                => $transaction->getName(),
                    'timestamp'           => $timestamp,
                    'duration'            => $duration,
                    'dropped_spans_stats' => null,
                    'context'             => null,
                    'outcome'             => $outcome,
                    'session'             => null,
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'add correct trace data to transaction record',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure() : AbstractTrace                                                                     $buildTraceEvent
     */
    function (Closure $buildTransaction, Closure $buildTraceEvent): void {
        $traceEvent  = $buildTraceEvent();
        $transaction = $buildTransaction(new Carbon(fake()->dateTime()), new Carbon(fake()->dateTime()), $traceEvent);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTimestamp')->andReturn(fake()->numberBetween(10000));
        $formaterMock->allows('getTransactionType')->andReturn(fake()->word());
        $formaterMock->allows('getOutcome')->andReturn(fake()->word());

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, new Collection(), []);

        expect($result[0]['transaction'])
            ->toMatchArray(
                [
                    'trace_id'    => $traceEvent->getTraceId(),
                    'parent_id'   => $traceEvent instanceof ExternalTrace ? $traceEvent->getId() : null,
                    'sample_rate' => $traceEvent instanceof StartTrace ? $traceEvent->sampleRate : null,
                    'sampled'     => $traceEvent->isSampled(),
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all trace events');

test(
    'add correct span counts to transaction record',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (
        Closure $buildTransaction,
        Collection $spans,
        array $spanRecords,
        int $expectedStarted,
        int $expectedDropped
    ): void {
        $transactionStartAt    = new Carbon(fake()->dateTime());
        $transactionFinishedAt = new Carbon(fake()->dateTime());
        $timestamp             = fake()->numberBetween(10000);
        $duration              = fake()->numberBetween(10000);
        $type                  = fake()->word();
        $outcome               = fake()->word();
        $transaction           = $buildTransaction($transactionStartAt, $transactionFinishedAt);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $transactionFinishedAt])->andReturn($duration);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$transactionStartAt])->andReturn($timestamp);
        $formaterMock->allows('getTransactionType')->andReturn($type);
        $formaterMock->allows('getOutcome')->andReturn($outcome);

        $transactionBuilder = new TransactionBuilder($formaterMock);
        $result             = $transactionBuilder->buildTransactionRecords($transaction, $spans, $spanRecords);

        expect($result[0]['transaction'])
            ->toMatchArray(['span_count' => ['started' => $expectedStarted, 'dropped' => $expectedDropped]]);
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'no spans and no span records' => [new Collection(), [], 0, 0],
            'more dropped as exists'       => [new Collection(['test']), ['test', 'test'], 1, 0],
            'nothing dropped'              => [new Collection(['test', 'test']), ['test', 'test'], 2, 0],
            'drop some'                    => [new Collection(['test', 'test', 'test', 'test']), ['test', 'test'], 4, 2],
            'only records'                 => [new Collection([]), ['test'], 0, 0],
        ]
    );
