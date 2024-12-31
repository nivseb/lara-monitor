<?php

namespace Tests\Component;

use Illuminate\Support\Facades\App;
use Nivseb\LaraMonitor\Contracts\Collector\ErrorCollectorContract;
use Nivseb\LaraMonitor\Contracts\Collector\SpanCollectorContract;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\TransactionCollectorContract;
use Nivseb\LaraMonitor\Contracts\AnalyserContract;
use Nivseb\LaraMonitor\Contracts\MapperContract;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Contracts\ApmServiceContract;
use Nivseb\LaraMonitor\Facades\LaraMonitorAnalyser;
use Nivseb\LaraMonitor\Facades\LaraMonitorApm;
use Nivseb\LaraMonitor\Facades\LaraMonitorError;
use Nivseb\LaraMonitor\Facades\LaraMonitorMapper;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Facades\LaraMonitorTransaction;
use Mockery;

test(
    'LaraMonitorAnalyser use AnalyserContract as facade accessor',
    function (): void {
        $serviceMock = Mockery::mock(AnalyserContract::class);
        App::bind(AnalyserContract::class, fn () => $serviceMock);

        expect(LaraMonitorAnalyser::getFacadeRoot())->toBe($serviceMock);
    }
);

test(
    'LaraMonitorAPM use ApmServiceContract as facade accessor',
    function (): void {
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);

        expect(LaraMonitorApm::getFacadeRoot())->toBe($serviceMock);
    }
);

test(
    'LaraMonitorError use ErrorCollectorContract as facade accessor',
    function (): void {
        $collectorMock = Mockery::mock(ErrorCollectorContract::class);
        App::bind(ErrorCollectorContract::class, fn () => $collectorMock);

        expect(LaraMonitorError::getFacadeRoot())->toBe($collectorMock);
    }
);

test(
    'LaraMonitorMapper use MapperContract as facade accessor',
    function (): void {
        $serviceMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $serviceMock);

        expect(LaraMonitorMapper::getFacadeRoot())->toBe($serviceMock);
    }
);

test(
    'LaraMonitorSpan use SpanCollectorContract as facade accessor',
    function (): void {
        $serviceMock = Mockery::mock(SpanCollectorContract::class);
        App::bind(SpanCollectorContract::class, fn () => $serviceMock);

        expect(LaraMonitorSpan::getFacadeRoot())->toBe($serviceMock);
    }
);

test(
    'LaraMonitorStore use RepositoryContract as facade accessor',
    function (): void {
        $repoMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $repoMock);

        expect(LaraMonitorStore::getFacadeRoot())->toBe($repoMock);
    }
);

test(
    'LaraMonitorTransaction use TransactionCollectorContract as facade accessor',
    function (): void {
        $serviceMock = Mockery::mock(TransactionCollectorContract::class);
        App::bind(TransactionCollectorContract::class, fn () => $serviceMock);

        expect(LaraMonitorTransaction::getFacadeRoot())->toBe($serviceMock);
    }
);
