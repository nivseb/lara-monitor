<?php

namespace Tests\Component\Services;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Nivseb\LaraMonitor\Contracts\ApmAgentContract;
use Nivseb\LaraMonitor\Contracts\AnalyserContract;
use Nivseb\LaraMonitor\Contracts\MapperContract;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Services\ApmService;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Mockery;
use Mockery\MockInterface;

test(
    'return agent name from config',
    function (): void {
        $name = fake()->words(6, true);

        Config::set('lara-monitor.service.agentName', $name);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        /** @var AnalyserContract&MockInterface $analyserMock */
        $analyserMock = Mockery::mock(AnalyserContract::class);
        App::bind(AnalyserContract::class, fn () => $analyserMock);

        $service = new ApmService();
        expect($service->getAgentName())->toBe($name);
    }
);

test(
    'allowErrorResponse set value to store as allowed exit code',
    function (): void {
        $allowedExitCode = fake()->numberBetween(1, 599);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        /** @var AnalyserContract&MockInterface $analyserMock */
        $analyserMock = Mockery::mock(AnalyserContract::class);
        App::bind(AnalyserContract::class, fn () => $analyserMock);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock
            ->allows('setAllowedExitCode')
            ->once()
            ->withArgs([$allowedExitCode]);

        $service = new ApmService();
        $service->allowErrorResponse($allowedExitCode);
    }
);

test(
    'finishCurrentTransaction dont fail without transaction',
    function (): void {
        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        /** @var AnalyserContract&MockInterface $analyserMock */
        $analyserMock = Mockery::mock(AnalyserContract::class);
        App::bind(AnalyserContract::class, fn () => $analyserMock);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var ApmAgentContract&MockInterface $apmAgentMock */
        $apmAgentMock = Mockery::mock(ApmAgentContract::class);
        App::bind(ApmAgentContract::class, fn () => $apmAgentMock);

        $storeMock
            ->allows('getTransaction')
            ->once()
            ->withNoArgs()
            ->andReturnNull();

        $storeMock
            ->allows('getSpanList')
            ->once()
            ->withNoArgs()
            ->andReturn(new Collection());

        $apmAgentMock->allows('sendData')->never();
        $analyserMock->allows('analyse')->never();

        $service = new ApmService();
        $service->finishCurrentTransaction();
    }
);

test(
    'finishCurrentTransaction dont fail without span list',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        /** @var AnalyserContract&MockInterface $analyserMock */
        $analyserMock = Mockery::mock(AnalyserContract::class);
        App::bind(AnalyserContract::class, fn () => $analyserMock);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var ApmAgentContract&MockInterface $apmAgentMock */
        $apmAgentMock = Mockery::mock(ApmAgentContract::class);
        App::bind(ApmAgentContract::class, fn () => $apmAgentMock);

        $storeMock
            ->allows('getTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn($transaction);

        $storeMock
            ->allows('getSpanList')
            ->once()
            ->withNoArgs()
            ->andReturnNull();

        $apmAgentMock->allows('sendData')->never();
        $analyserMock->allows('analyse')->never();

        $service = new ApmService();
        $service->finishCurrentTransaction();
    }
)
    ->with('all possible transaction types');

test(
    'finishCurrentTransaction analyse data but dont send if no agent is registered',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();
        $spans       = new Collection();

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        /** @var AnalyserContract&MockInterface $analyserMock */
        $analyserMock = Mockery::mock(AnalyserContract::class);
        App::bind(AnalyserContract::class, fn () => $analyserMock);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock
            ->allows('getTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn($transaction);

        $storeMock
            ->allows('getSpanList')
            ->once()
            ->withNoArgs()
            ->andReturn($spans);

        $storeMock
            ->allows('getAllowedExitCode')
            ->once()
            ->withNoArgs()
            ->andReturnNull();

        $analyserMock->allows('analyse')
            ->once()
            ->withArgs([$transaction, $spans, null]);

        $service = new ApmService();
        $service->finishCurrentTransaction();
    }
)
    ->with('all possible transaction types');

test(
    'finishCurrentTransaction send data with registered apm agent',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();
        $spans       = new Collection();

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        /** @var AnalyserContract&MockInterface $analyserMock */
        $analyserMock = Mockery::mock(AnalyserContract::class);
        App::bind(AnalyserContract::class, fn () => $analyserMock);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var ApmAgentContract&MockInterface $apmAgentMock */
        $apmAgentMock = Mockery::mock(ApmAgentContract::class);
        App::bind(ApmAgentContract::class, fn () => $apmAgentMock);

        $storeMock
            ->allows('getTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn($transaction);

        $storeMock
            ->allows('getSpanList')
            ->once()
            ->withNoArgs()
            ->andReturn($spans);

        $storeMock
            ->allows('getAllowedExitCode')
            ->once()
            ->withNoArgs()
            ->andReturnNull();

        $analyserMock->allows('analyse')
            ->once()
            ->withArgs([$transaction, $spans, null]);

        $apmAgentMock->allows('sendData')
            ->once()
            ->withArgs([$transaction, $spans]);

        $service = new ApmService();
        $service->finishCurrentTransaction();
    }
)
    ->with('all possible transaction types');

test(
    'allowed exit code is given to analyser',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction     = $buildTransaction();
        $spans           = new Collection();
        $allowedExitCode = fake()->numberBetween(1, 599);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        /** @var AnalyserContract&MockInterface $analyserMock */
        $analyserMock = Mockery::mock(AnalyserContract::class);
        App::bind(AnalyserContract::class, fn () => $analyserMock);

        /** @var RepositoryContract&MockInterface $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var ApmAgentContract&MockInterface $apmAgentMock */
        $apmAgentMock = Mockery::mock(ApmAgentContract::class);
        App::bind(ApmAgentContract::class, fn () => $apmAgentMock);

        $storeMock
            ->allows('getTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn($transaction);

        $storeMock
            ->allows('getSpanList')
            ->once()
            ->withNoArgs()
            ->andReturn($spans);

        $storeMock
            ->allows('getAllowedExitCode')
            ->once()
            ->withNoArgs()
            ->andReturn($allowedExitCode);

        $analyserMock->allows('analyse')
            ->once()
            ->withArgs([$transaction, $spans, $allowedExitCode]);

        $apmAgentMock->allows('sendData')
            ->once()
            ->withArgs([$transaction, $spans]);

        $service = new ApmService();
        $service->finishCurrentTransaction();
    }
)
    ->with('all possible transaction types');
