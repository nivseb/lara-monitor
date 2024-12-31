<?php

namespace Tests\Component\Elastic;

use Carbon\CarbonInterface;
use Closure;
use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Elastic\LogDataProcessor;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

test(
    'log data processor implements ProcessorInterface',
    function (): void {
        $logDataProcessor = new LogDataProcessor();
        expect($logDataProcessor)
            ->toBeInstanceOf(ProcessorInterface::class)
            ->toBeCallable();
    }
);

test(
    'log data processor return unchanged log record with disabled monitoring',
    function (): void {
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        Config::set('lara-monitor.enabled', false);

        $logDataProcessor  = new LogDataProcessor();
        $expectedLogRecord = new LogRecord(
            new DateTimeImmutable('now'),
            fake()->word(),
            fake()->randomElement(Level::cases()),
            (string) fake()->words(10, true),
        );

        $logRecord = $logDataProcessor($expectedLogRecord);

        expect($logRecord)
            ->toBe($expectedLogRecord)
            ->and($expectedLogRecord->extra)
            ->toBeArray()
            ->toHaveCount(0)
            ->toBe([]);
    }
);

test(
    'log data processor add service data to extra',
    function (): void {
        $name        = fake()->word();
        $version     = fake()->semver();
        $environment = fake()->word();

        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);
        $storeMock->allows('getTransaction')->andReturnNull();
        $storeMock->allows('getCurrentTraceEvent')->andReturnNull();

        Config::set('lara-monitor.enabled', true);
        Config::set('lara-monitor.service.name', $name);
        Config::set('lara-monitor.service.version', $version);
        Config::set('lara-monitor.service.env', $environment);

        $logDataProcessor  = new LogDataProcessor();
        $expectedLogRecord = new LogRecord(
            new DateTimeImmutable('now'),
            fake()->word(),
            fake()->randomElement(Level::cases()),
            (string) fake()->words(10, true),
        );

        $logRecord = $logDataProcessor($expectedLogRecord);

        expect($logRecord->extra)
            ->toHaveKey('service')
            ->and($logRecord->extra['service'])
            ->toBe(
                [
                    'name'        => $name,
                    'version'     => $version,
                    'environment' => $environment,
                ]
            );
    }
);

test(
    'log data processor add service and container data to extra even transaction access fail',
    function (): void {
        $name        = fake()->word();
        $version     = fake()->semver();
        $environment = fake()->word();
        $containerId = fake()->uuid();

        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);
        $storeMock->allows('getTransaction')->andThrow(new Exception());
        $storeMock->allows('getCurrentTraceEvent')->andReturnNull();

        Config::set('lara-monitor.enabled', true);
        Config::set('lara-monitor.service.name', $name);
        Config::set('lara-monitor.service.version', $version);
        Config::set('lara-monitor.service.env', $environment);
        Config::set('lara-monitor.instance.containerId', $containerId);

        $logDataProcessor  = new LogDataProcessor();
        $expectedLogRecord = new LogRecord(
            new DateTimeImmutable('now'),
            fake()->word(),
            fake()->randomElement(Level::cases()),
            (string) fake()->words(10, true),
        );

        $logRecord = $logDataProcessor($expectedLogRecord);

        expect($logRecord->extra)
            ->toHaveCount(2)
            ->toHaveKeys(['service', 'container'])
            ->and($logRecord->extra['service'])
            ->toBe(
                [
                    'name'        => $name,
                    'version'     => $version,
                    'environment' => $environment,
                ]
            )
            ->and($logRecord->extra['container'])
            ->toBe(['id' => $containerId]);
    }
);

test(
    'log data processor add service and container data to extra even span access fail',
    function (): void {
        $name        = fake()->word();
        $version     = fake()->semver();
        $environment = fake()->word();
        $containerId = fake()->uuid();

        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);
        $storeMock->allows('getTransaction')->andReturnNull();
        $storeMock->allows('getCurrentTraceEvent')->andThrow(new Exception());

        Config::set('lara-monitor.enabled', true);
        Config::set('lara-monitor.service.name', $name);
        Config::set('lara-monitor.service.version', $version);
        Config::set('lara-monitor.service.env', $environment);
        Config::set('lara-monitor.instance.containerId', $containerId);

        $logDataProcessor  = new LogDataProcessor();
        $expectedLogRecord = new LogRecord(
            new DateTimeImmutable('now'),
            fake()->word(),
            fake()->randomElement(Level::cases()),
            (string) fake()->words(10, true),
        );

        $logRecord = $logDataProcessor($expectedLogRecord);

        expect($logRecord->extra)
            ->toHaveCount(2)
            ->toHaveKeys(['service', 'container'])
            ->and($logRecord->extra['service'])
            ->toBe(
                [
                    'name'        => $name,
                    'version'     => $version,
                    'environment' => $environment,
                ]
            )
            ->and($logRecord->extra['container'])
            ->toBe(['id' => $containerId]);
    }
);

test(
    'log data processor add trace id to extra data',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();

        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);
        $storeMock->allows('getTransaction')->andReturn($transaction);
        $storeMock->allows('getCurrentTraceEvent')->andReturnNull();

        Config::set('lara-monitor.enabled', true);

        $logDataProcessor  = new LogDataProcessor();
        $expectedLogRecord = new LogRecord(
            new DateTimeImmutable('now'),
            fake()->word(),
            fake()->randomElement(Level::cases()),
            (string) fake()->words(10, true),
        );

        $logRecord = $logDataProcessor($expectedLogRecord);

        expect($logRecord->extra)
            ->toHaveKey('trace')
            ->and($logRecord->extra['trace'])
            ->toBe(['id' => $transaction->getTraceId()]);
    }
)
    ->with('all possible transaction types');

test(
    'log data processor add transaction id to extra data',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();

        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);
        $storeMock->allows('getTransaction')->andReturn($transaction);
        $storeMock->allows('getCurrentTraceEvent')->andReturnNull();

        Config::set('lara-monitor.enabled', true);

        $logDataProcessor  = new LogDataProcessor();
        $expectedLogRecord = new LogRecord(
            new DateTimeImmutable('now'),
            fake()->word(),
            fake()->randomElement(Level::cases()),
            (string) fake()->words(10, true),
        );

        $logRecord = $logDataProcessor($expectedLogRecord);

        expect($logRecord->extra)
            ->toHaveKey('transaction')
            ->and($logRecord->extra['transaction'])
            ->toBe(['id' => $transaction->getId()]);
    }
)
    ->with('all possible transaction types');

test(
    'log data processor add span id to extra data',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                    $buildSpan
     */
    function (Closure $buildTransaction, Closure $buildSpan): void {
        $span = $buildSpan($buildTransaction());

        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);
        $storeMock->allows('getTransaction')->andReturnNull();
        $storeMock->allows('getCurrentTraceEvent')->andReturn($span);

        Config::set('lara-monitor.enabled', true);

        $logDataProcessor  = new LogDataProcessor();
        $expectedLogRecord = new LogRecord(
            new DateTimeImmutable('now'),
            fake()->word(),
            fake()->randomElement(Level::cases()),
            (string) fake()->words(10, true),
        );

        $logRecord = $logDataProcessor($expectedLogRecord);

        expect($logRecord->extra)
            ->toHaveKey('span')
            ->and($logRecord->extra['span'])
            ->toBe(['id' => $span->getId()]);
    }
)
    ->with('all possible transaction types')
    ->with('all possible span types');
