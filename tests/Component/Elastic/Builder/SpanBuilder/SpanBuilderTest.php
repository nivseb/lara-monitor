<?php

namespace Tests\Component\Elastic\Builder\SpanBuilder;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Elastic\Builder\SpanBuilder;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Mockery;
use Mockery\MockInterface;

test(
    'dont build spans for transactions that is not started yet ',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(null, Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'dont build spans for transactions that is not finished yet ',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(now()->subMinute(), null);
        $span        = $buildSpan($transaction);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'dont build spans for no given spans',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection());

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types');

test(
    'add wrapper for each span',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span1       = $buildSpan($transaction);
        $span2       = $buildSpan($transaction);
        $span3       = $buildSpan($transaction);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span1, $span2, $span3]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(3)
            ->and($result[0])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('span')
            ->and($result[1])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('span')
            ->and($result[2])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('span');
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add correct ids to span with transaction as parent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toBeArray()
            ->toMatchArray(
                [
                    'id'        => $span->getId(),
                    'parent_id' => $transaction->getId(),
                    'trace_id'  => $transaction->getTraceId(),
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add correct ids to span with other span as parent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpanParent, Closure $buildSpan): void {
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $span        = $buildSpan($spanParent);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toBeArray()
            ->toMatchArray(
                [
                    'id'        => $span->getId(),
                    'parent_id' => $spanParent->getId(),
                    'trace_id'  => $transaction->getTraceId(),
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types')
    ->with('all possible span types');

test(
    'add correct base data to span with transaction as parent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toBeArray()
            ->toMatchArray(
                [
                    'name'        => $span->getName(),
                    'sync'        => true,
                    'sample_rate' => 1,
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add correct base data to span with other span as parent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpanParent, Closure $buildSpan): void {
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $span        = $buildSpan($spanParent);

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toBeArray()
            ->toMatchArray(
                [
                    'name'        => $span->getName(),
                    'sync'        => true,
                    'sample_rate' => 1,
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types')
    ->with('all possible span types');

test(
    'add correct time data for span that are determined with formater with transaction as parent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt = new Carbon(fake()->dateTime());
        $spanStartAt        = new Carbon(fake()->dateTime());
        $spanFinishedAt     = new Carbon(fake()->dateTime());
        $duration           = fake()->randomFloat(2);
        $start              = fake()->randomFloat(2);
        $timestamp          = fake()->numberBetween(10000);
        $transaction        = $buildTransaction($transactionStartAt, Carbon::now()->subSecond());
        $span               = $buildSpan($transaction);
        $span->startAt      = $spanStartAt;
        $span->finishAt     = $spanFinishedAt;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$spanStartAt, $spanFinishedAt])->andReturn($duration);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $spanStartAt])->andReturn($start);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$spanStartAt])->andReturn($timestamp);
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toBeArray()
            ->toMatchArray(
                [
                    'timestamp' => $timestamp,
                    'duration'  => $duration,
                    'start'     => $start,
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add correct time data for span that are determined with formater with other span as parent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpanParent, Closure $buildSpan): void {
        $transactionStartAt = new Carbon(fake()->dateTime());
        $spanStartAt        = new Carbon(fake()->dateTime());
        $spanFinishedAt     = new Carbon(fake()->dateTime());
        $duration           = fake()->randomFloat(2);
        $start              = fake()->randomFloat(2);
        $timestamp          = fake()->numberBetween(10000);
        $transaction        = $buildTransaction($transactionStartAt, Carbon::now()->subSecond());
        $spanParent         = $buildSpanParent($transaction);
        $span               = $buildSpan($spanParent);
        $span->startAt      = $spanStartAt;
        $span->finishAt     = $spanFinishedAt;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$spanStartAt, $spanFinishedAt])->andReturn($duration);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $spanStartAt])->andReturn($start);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$spanStartAt])->andReturn($timestamp);
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toBeArray()
            ->toMatchArray(
                [
                    'timestamp' => $timestamp,
                    'duration'  => $duration,
                    'start'     => $start,
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types')
    ->with('all possible span types');

test(
    'dont build span record without duration',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt = new Carbon(fake()->dateTime());
        $spanStartAt        = new Carbon(fake()->dateTime());
        $spanFinishedAt     = new Carbon(fake()->dateTime());
        $start              = fake()->randomFloat(2);
        $timestamp          = fake()->numberBetween(10000);
        $transaction        = $buildTransaction($transactionStartAt, Carbon::now()->subSecond());
        $span               = $buildSpan($transaction);
        $span->startAt      = $spanStartAt;
        $span->finishAt     = $spanFinishedAt;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$spanStartAt, $spanFinishedAt])->andReturnNull();
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $spanStartAt])->andReturn($start);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$spanStartAt])->andReturn($timestamp);
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'build span record duration that is zero',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt = new Carbon(fake()->dateTime());
        $spanStartAt        = new Carbon(fake()->dateTime());
        $spanFinishedAt     = new Carbon(fake()->dateTime());
        $start              = fake()->randomFloat(2);
        $timestamp          = fake()->numberBetween(10000);
        $transaction        = $buildTransaction($transactionStartAt, Carbon::now()->subSecond());
        $span               = $buildSpan($transaction);
        $span->startAt      = $spanStartAt;
        $span->finishAt     = $spanFinishedAt;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$spanStartAt, $spanFinishedAt])->andReturn(0);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $spanStartAt])->andReturn($start);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$spanStartAt])->andReturn($timestamp);
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'dont build span record without start',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt = new Carbon(fake()->dateTime());
        $spanStartAt        = new Carbon(fake()->dateTime());
        $spanFinishedAt     = new Carbon(fake()->dateTime());
        $duration           = fake()->randomFloat(2);
        $timestamp          = fake()->numberBetween(10000);
        $transaction        = $buildTransaction($transactionStartAt, Carbon::now()->subSecond());
        $span               = $buildSpan($transaction);
        $span->startAt      = $spanStartAt;
        $span->finishAt     = $spanFinishedAt;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$spanStartAt, $spanFinishedAt])->andReturn($duration);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $spanStartAt])->andReturnNull();
        $formaterMock->allows('getTimestamp')->once()->withArgs([$spanStartAt])->andReturn($timestamp);
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'build span record if start that is zero',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt = new Carbon(fake()->dateTime());
        $spanStartAt        = new Carbon(fake()->dateTime());
        $spanFinishedAt     = new Carbon(fake()->dateTime());
        $duration           = fake()->randomFloat(2);
        $timestamp          = fake()->numberBetween(10000);
        $transaction        = $buildTransaction($transactionStartAt, Carbon::now()->subSecond());
        $span               = $buildSpan($transaction);
        $span->startAt      = $spanStartAt;
        $span->finishAt     = $spanFinishedAt;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$spanStartAt, $spanFinishedAt])->andReturn($duration);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $spanStartAt])->andReturn(0);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$spanStartAt])->andReturn($timestamp);
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'dont build span record without timestamp',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt = new Carbon(fake()->dateTime());
        $spanStartAt        = new Carbon(fake()->dateTime());
        $spanFinishedAt     = new Carbon(fake()->dateTime());
        $duration           = fake()->randomFloat(2);
        $start              = fake()->randomFloat(2);
        $transaction        = $buildTransaction($transactionStartAt, Carbon::now()->subSecond());
        $span               = $buildSpan($transaction);
        $span->startAt      = $spanStartAt;
        $span->finishAt     = $spanFinishedAt;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$spanStartAt, $spanFinishedAt])->andReturn($duration);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $spanStartAt])->andReturn($start);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$spanStartAt])->andReturnNull();
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'dont build span record if timestamp that is zero',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transactionStartAt = new Carbon(fake()->dateTime());
        $spanStartAt        = new Carbon(fake()->dateTime());
        $spanFinishedAt     = new Carbon(fake()->dateTime());
        $duration           = fake()->randomFloat(2);
        $start              = fake()->randomFloat(2);
        $transaction        = $buildTransaction($transactionStartAt, Carbon::now()->subSecond());
        $span               = $buildSpan($transaction);
        $span->startAt      = $spanStartAt;
        $span->finishAt     = $spanFinishedAt;

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn(new TypeData(fake()->word()));
        $formaterMock->allows('calcDuration')->once()->withArgs([$spanStartAt, $spanFinishedAt])->andReturn($duration);
        $formaterMock->allows('calcDuration')->once()->withArgs([$transactionStartAt, $spanStartAt])->andReturn($start);
        $formaterMock->allows('getTimestamp')->once()->withArgs([$spanStartAt])->andReturn(0);
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add correct type data to span with transaction as parent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);
        $typeData    = new TypeData(fake()->word(), fake()->word(), fake()->word());

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn($typeData);
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toBeArray()
            ->toMatchArray(
                [
                    'type'    => $typeData->type,
                    'subtype' => $typeData->subType,
                    'action'  => $typeData->action,
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');

test(
    'add correct type data to span with other span as parent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpanParent
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpanParent, Closure $buildSpan): void {
        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $spanParent  = $buildSpanParent($transaction);
        $span        = $buildSpan($spanParent);
        $typeData    = new TypeData(fake()->word(), fake()->word(), fake()->word());

        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);
        $formaterMock->allows('getSpanTypeData')->andReturn($typeData);
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['span'])
            ->toBeArray()
            ->toMatchArray(
                [
                    'type'    => $typeData->type,
                    'subtype' => $typeData->subType,
                    'action'  => $typeData->action,
                ]
            );
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types')
    ->with('all possible span types');

test(
    'dont build span record without type data missing',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        /** @var ElasticFormaterContract&MockInterface $formaterMock */
        $formaterMock = Mockery::mock(ElasticFormaterContract::class);

        $transaction = $buildTransaction(now()->subMinute(), Carbon::now()->subSecond());
        $span        = $buildSpan($transaction);

        $formaterMock->allows('getSpanTypeData')->once()->withArgs([$span])->andReturnNull();
        $formaterMock->allows('calcDuration')->andReturnUsing(fn () => fake()->randomFloat());
        $formaterMock->allows('getTimestamp')->andReturnUsing(fn () => fake()->numberBetween(10000));
        $formaterMock->allows('getOutcome')->andReturn('success');

        $spanBuilder = new SpanBuilder($formaterMock);
        $result      = $spanBuilder->buildSpanRecords($transaction, new Collection([$span]));

        expect($result)
            ->toBeArray()
            ->toHaveCount(0);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');
