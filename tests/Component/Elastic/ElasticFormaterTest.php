<?php

namespace Tests\Component\Elastic;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use GuzzleHttp\Psr7\Uri;
use Nivseb\LaraMonitor\Elastic\ElasticFormater;
use Nivseb\LaraMonitor\Enums\Elastic\Outcome;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\PlainSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Spans\RenderSpan;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\OctaneRequestTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

test(
    'get correct transaction type for transaction class',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, string $expectedType): void {
        $formater = new ElasticFormater();
        expect($formater->getTransactionType($buildTransaction()))->toBe($expectedType);
    }
)
    ->with(
        [
            'reuqest'        => [fn () => new RequestTransaction(new StartTrace(false, 0.0)), 'request'],
            'octane reuqest' => [fn () => new OctaneRequestTransaction(new StartTrace(false, 0.0)), 'request'],
            'command'        => [fn () => new CommandTransaction(new StartTrace(false, 0.0)), 'command'],
            'job'            => [fn () => new JobTransaction(new StartTrace(false, 0.0)), 'job'],
        ]
    );

test(
    'get `null` as outcome if the successful property on the given child trace is `null`',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent             = $buildTraceChild();
        $traceEvent->successful = null;
        $formater               = new ElasticFormater();
        expect($formater->getOutcome($traceEvent))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'get `success` as outcome if the successful property on the given child trace is `true`',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent             = $buildTraceChild();
        $traceEvent->successful = true;
        $formater               = new ElasticFormater();
        expect($formater->getOutcome($traceEvent))->toBe(Outcome::Success);
    }
)
    ->with('all possible child trace events');

test(
    'get `failure` as outcome if the successful property on the given child trace is `false`',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent             = $buildTraceChild();
        $traceEvent->successful = false;
        $formater               = new ElasticFormater();
        expect($formater->getOutcome($traceEvent))->toBe(Outcome::Failure);
    }
)
    ->with('all possible child trace events');

test(
    'get correct for given date',
    function (?CarbonInterface $date, ?int $expectedTimestamp): void {
        $formater = new ElasticFormater();
        expect($formater->getTimestamp($date))->toBe($expectedTimestamp);
    }
)
    ->with(
        [
            'no date'        => [null, null],
            'existing date ' => [new Carbon('2024-12-21 14:36:54.543'), 1734791814543000],
        ]
    );

test(
    'calc durations correct',
    function (?CarbonInterface $start, ?CarbonInterface $end, ?float $expectedDuration): void {
        $formater = new ElasticFormater();
        expect($formater->calcDuration($start, $end))->toBe($expectedDuration);
    }
)
    ->with(
        [
            'no date'    => [null, null, null],
            'only start' => [now(), null, null],
            'only end'   => [null, Carbon::now(), null],
            'one second' => [
                new Carbon('2024-12-21 14:36:54.543'),
                new Carbon('2024-12-21 14:36:55.543'),
                1000.0],
            'one millisecond' => [
                new Carbon('2024-12-21 14:36:54.543'),
                new Carbon('2024-12-21 14:36:54.544'),
                1.0],
            'one microsecond' => [
                new Carbon('2024-12-21 14:36:54.543'),
                Carbon::parse('2024-12-21 14:36:54.543')->addMicrosecond(),
                0.001],
        ]
    );

test(
    'map database type as sub type for query span',
    function (string $databaseType): void {
        $formater  = new ElasticFormater();
        $querySpan = new QuerySpan(
            '',
            [],
            new RequestTransaction(new StartTrace(false, 0.0)),
            Carbon::now(),
            Carbon::now()
        );
        $querySpan->databaseType = $databaseType;

        /** @var TypeData $typeData */
        $typeData = $formater->getSpanTypeData($querySpan);
        expect($typeData)
            ->toBeInstanceOf(TypeData::class)
            ->and($typeData->type)->toBe('db')
            ->and($typeData->subType)->toBe($databaseType)
            ->and($typeData->action)->toBe('query');
    }
)
    ->with(
        [
            'mysql'      => ['mysql'],
            'mariadb'    => ['mariadb'],
            'mssql'      => ['mssql'],
            'sqlite'     => ['sqlite'],
            'postgresql' => ['postgresql'],
            'myDriver'   => ['myDriver'],
        ]
    );

test(
    'map type data for redis span',
    function (): void {
        $formater  = new ElasticFormater();
        $querySpan = new RedisCommandSpan(
            '',
            '',
            new RequestTransaction(new StartTrace(false, 0.0)),
            Carbon::now(),
            Carbon::now()
        );

        /** @var TypeData $typeData */
        $typeData = $formater->getSpanTypeData($querySpan);
        expect($typeData)
            ->toBeInstanceOf(TypeData::class)
            ->and($typeData->type)->toBe('db')
            ->and($typeData->subType)->toBe('redis')
            ->and($typeData->action)->toBe('query');
    }
);

test(
    'map type data for http span',
    function (): void {
        $formater  = new ElasticFormater();
        $querySpan = new HttpSpan(
            '',
            new Uri('/'),
            new RequestTransaction(new StartTrace(false, 0.0)),
            Carbon::now()
        );

        /** @var TypeData $typeData */
        $typeData = $formater->getSpanTypeData($querySpan);
        expect($typeData)
            ->toBeInstanceOf(TypeData::class)
            ->and($typeData->type)->toBe('external')
            ->and($typeData->subType)->toBe('http')
            ->and($typeData->action)->toBeNull();
    }
);

test(
    'map response type as sub type for render span',
    function (string $responseType): void {
        $formater = new ElasticFormater();
        $span     = new RenderSpan(
            '',
            new RequestTransaction(new StartTrace(false, 0.0)),
            Carbon::now(),
            Carbon::now()
        );
        $span->type = $responseType;

        /** @var TypeData $typeData */
        $typeData = $formater->getSpanTypeData($span);
        expect($typeData)
            ->toBeInstanceOf(TypeData::class)
            ->and($typeData->type)->toBe('template')
            ->and($typeData->subType)->toBe($responseType)
            ->and($typeData->action)->toBe('render');
    }
)
    ->with(
        [
            'view'     => ['view'],
            'resource' => ['resource'],
            'json'     => ['json'],
            'response' => ['response'],
            'other'    => ['other'],
        ]
    );

test(
    'map type for plain span',
    function (): void {
        $expectedType = fake()->word();

        $formater  = new ElasticFormater();
        $querySpan = new PlainSpan(
            fake()->word(),
            $expectedType,
            new RequestTransaction(new StartTrace(false, 0.0)),
            Carbon::now()
        );

        /** @var TypeData $typeData */
        $typeData = $formater->getSpanTypeData($querySpan);
        expect($typeData)
            ->toBeInstanceOf(TypeData::class)
            ->and($typeData->type)->toBe($expectedType)
            ->and($typeData->subType)->toBeNull()
            ->and($typeData->action)->toBeNull();
    }
);

test(
    'map type for system span',
    function (): void {
        $expectedType = fake()->word();

        $formater  = new ElasticFormater();
        $querySpan = new SystemSpan(
            fake()->word(),
            $expectedType,
            new RequestTransaction(new StartTrace(false, 0.0)),
            Carbon::now()
        );

        /** @var TypeData $typeData */
        $typeData = $formater->getSpanTypeData($querySpan);
        expect($typeData)
            ->toBeInstanceOf(TypeData::class)
            ->and($typeData->type)->toBe($expectedType)
            ->and($typeData->subType)->toBeNull()
            ->and($typeData->action)->toBeNull();
    }
);
