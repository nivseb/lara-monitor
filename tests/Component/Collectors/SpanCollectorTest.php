<?php

namespace Tests\Component\Collectors;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\App;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Collectors\SpanCollector;
use Nivseb\LaraMonitor\Contracts\Collector\ErrorCollectorContract;
use Nivseb\LaraMonitor\Contracts\MapperContract;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\PlainSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Spans\RenderSpan;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Psr\Http\Message\RequestInterface;
use Throwable;

test(
    'startAction dont create plain span without current trace event',
    function (): void {
        $name = fake()->word();
        $type = fake()->word();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $collector = new SpanCollector();
        expect($collector->startAction($name, $type))->toBeNull();
    }
);

test(
    'startAction dont create plain span with completed current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = Carbon::now();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $collector = new SpanCollector();
        expect($collector->startAction($name, $type))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'startAction create plain span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildPlainSpan')
            ->once()
            ->withArgs([$parentTraceEvent, $name, $type, $subType, $date])
            ->andReturnNull();

        $storeMock->allows('addSpan')->never();

        $collector = new SpanCollector();
        expect($collector->startAction($name, $type, $subType, $date))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'startAction create plain span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new PlainSpan($name, $type, $parentTraceEvent, $date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildPlainSpan')
            ->once()
            ->withArgs([$parentTraceEvent, $name, $type, $subType, $date])
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->startAction($name, $type, $subType, $date))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'startAction create plain span and use current time with no given start at',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new PlainSpan($name, $type, $parentTraceEvent, $date);
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildPlainSpan')
            ->once()
            ->withArgs(fn (...$args) => $date->eq($args[4]))
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->startAction($name, $type, $subType))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'startAction dont create system span without current trace event',
    function (): void {
        $name = fake()->word();
        $type = fake()->word();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $collector = new SpanCollector();
        expect($collector->startAction($name, $type, system: true))->toBeNull();
    }
);

test(
    'startAction create system span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildSystemSpan')
            ->once()
            ->withArgs([$parentTraceEvent, $name, $type, $subType, $date])
            ->andReturnNull();

        $storeMock->allows('addSpan')->never();

        $collector = new SpanCollector();
        expect($collector->startAction($name, $type, $subType, $date, true))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'startAction create system span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new SystemSpan($name, $type, $parentTraceEvent, $date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildSystemSpan')
            ->once()
            ->withArgs([$parentTraceEvent, $name, $type, $subType, $date])
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->startAction($name, $type, $subType, $date, true))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'startAction create system span and use current time with no given start at',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new SystemSpan($name, $type, $parentTraceEvent, $date);
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildSystemSpan')
            ->once()
            ->withArgs(fn (...$args) => $date->eq($args[4]))
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->startAction($name, $type, $subType, system: true))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'startHttpAction dont create span with completed current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = Carbon::now();

        /** @var MockInterface&RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $collector = new SpanCollector();
        expect($collector->startHttpAction($request))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'startHttpAction dont create span without current trace event',
    function (): void {
        /** @var MockInterface&RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $collector = new SpanCollector();
        expect($collector->startHttpAction($request))->toBeNull();
    }
);

test(
    'startHttpAction create span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;

        /** @var MockInterface&RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildHttpSpanFromRequest')
            ->once()
            ->withArgs([$parentTraceEvent, $request, $date])
            ->andReturnNull();

        $storeMock->allows('addSpan')->never();

        $collector = new SpanCollector();
        expect($collector->startHttpAction($request, $date))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'startHttpAction create span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new HttpSpan('GET', new Uri('/'), $parentTraceEvent, Carbon::now());

        /** @var MockInterface&RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildHttpSpanFromRequest')
            ->once()
            ->withArgs([$parentTraceEvent, $request, $date])
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->startHttpAction($request, $date))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'startHttpAction create span and use current time with no given start at',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new HttpSpan('GET', new Uri('/'), $parentTraceEvent, Carbon::now());
        Carbon::setTestNow($date);

        /** @var MockInterface&RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildHttpSpanFromRequest')
            ->once()
            ->withArgs(fn (...$args) => $date->eq($args[2]))
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->startHttpAction($request))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'startRenderAction dont create span without current trace event',
    function (): void {
        $response = fake()->text();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $collector = new SpanCollector();
        expect($collector->startRenderAction($response))->toBeNull();
    }
);

test(
    'startRenderAction dont create span with completed current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $response                   = fake()->text();
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = Carbon::now();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $collector = new SpanCollector();
        expect($collector->startRenderAction($response))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'startRenderAction create span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $response                   = fake()->text();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildRenderSpanForResponse')
            ->once()
            ->withArgs([$parentTraceEvent, $response, $date])
            ->andReturnNull();

        $storeMock->allows('addSpan')->never();

        $collector = new SpanCollector();
        expect($collector->startRenderAction($response, $date))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'startRenderAction create span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $response                   = fake()->text();
        $span                       = new RenderSpan('', $parentTraceEvent, Carbon::now());

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildRenderSpanForResponse')
            ->once()
            ->withArgs([$parentTraceEvent, $response, $date])
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->startRenderAction($response, $date))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'startRenderAction create span and use current time with no given start at',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $response                   = fake()->text();
        $span                       = new RenderSpan('', $parentTraceEvent, Carbon::now());
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildRenderSpanForResponse')
            ->once()
            ->withArgs(fn (...$args) => $date->eq($args[2]))
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->startRenderAction($response))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'stopAction not stop if no current trace event exists',
    function (): void {
        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $collector = new SpanCollector();
        expect($collector->stopAction())->toBeNull();
    }
);

test(
    'stopAction not stop if current trace event is transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($transaction);

        $collector = new SpanCollector();
        expect($collector->stopAction())->toBeNull();
    }
)
    ->with('all possible transaction types');

test(
    'stopAction stop current trace event and parent as current trace event with given time',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractChildTraceEvent $buildParent
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                        $buildSpan
     */
    function (Closure $buildParent, Closure $buildSpan): void {
        $date              = new Carbon(fake()->dateTime());
        $parentTraceEvent  = $buildParent();
        $currentTraceEvent = $buildSpan($parentTraceEvent);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $currentTraceEvent->finishAt = null;

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($currentTraceEvent);
        $storeMock->allows('setCurrentTraceEvent')->once()->withArgs([$parentTraceEvent])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->stopAction($date))
            ->toBe($parentTraceEvent)
            ->and($currentTraceEvent->finishAt)->toEqual($date);
    }
)
    ->with('all possible child trace events')
    ->with('all possible span types');

test(
    'stopAction stop current trace event and parent as current trace event with no given time',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractChildTraceEvent $buildParent
     * @param Closure(AbstractChildTraceEvent) : AbstractChildTraceEvent                                        $buildSpan
     */
    function (Closure $buildParent, Closure $buildSpan): void {
        $date              = new Carbon(fake()->dateTime());
        $parentTraceEvent  = $buildParent();
        $currentTraceEvent = $buildSpan($parentTraceEvent);
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $currentTraceEvent->finishAt = null;

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($currentTraceEvent);
        $storeMock->allows('setCurrentTraceEvent')->once()->withArgs([$parentTraceEvent])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->stopAction())
            ->toBe($parentTraceEvent)
            ->and($currentTraceEvent->finishAt)->toEqual($date);
    }
)
    ->with('all possible child trace events')
    ->with('all possible span types');

test(
    'trackDatabaseQuery dont create span without current trace event',
    function (): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $collector = new SpanCollector();
        expect($collector->trackDatabaseQuery($queryEvent))->toBeNull();
    }
);

test(
    'trackDatabaseQuery dont create span with completed current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = Carbon::now();

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $collector = new SpanCollector();
        expect($collector->trackDatabaseQuery($queryEvent))->toBeNull();
    }
)
    ->with('all possible child trace events');
test(
    'trackDatabaseQuery create span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildQuerySpanFromExecuteEvent')
            ->once()
            ->withArgs([$parentTraceEvent, $queryEvent, $date])
            ->andReturnNull();

        $storeMock->allows('addSpan')->never();

        $collector = new SpanCollector();
        expect($collector->trackDatabaseQuery($queryEvent, $date))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'trackDatabaseQuery create span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new QuerySpan('SELECT', ['exampleTable'], $parentTraceEvent, $date, $date);

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildQuerySpanFromExecuteEvent')
            ->once()
            ->withArgs([$parentTraceEvent, $queryEvent, $date])
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->trackDatabaseQuery($queryEvent, $date))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'trackDatabaseQuery create span and use current time with no given start at',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new QuerySpan('SELECT', ['exampleTable'], $parentTraceEvent, $date, $date);
        Carbon::setTestNow($date);

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildQuerySpanFromExecuteEvent')
            ->once()
            ->withArgs(fn (...$args) => $date->eq($args[2]))
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->trackDatabaseQuery($queryEvent))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'trackRedisCommand dont create span without current trace event',
    function (): void {
        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $collector = new SpanCollector();
        expect($collector->trackRedisCommand($commandEvent))->toBeNull();
    }
);

test(
    'trackRedisCommand dont create span with completed current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = Carbon::now();

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $collector = new SpanCollector();
        expect($collector->trackRedisCommand($commandEvent))->toBeNull();
    }
)
    ->with('all possible child trace events');
test(
    'trackRedisCommand create span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildRedisSpanFromExecuteEvent')
            ->once()
            ->withArgs([$parentTraceEvent, $commandEvent, $date])
            ->andReturnNull();

        $storeMock->allows('addSpan')->never();

        $collector = new SpanCollector();
        expect($collector->trackRedisCommand($commandEvent, $date))->toBeNull();
    }
)
    ->with('all possible child trace events');

test(
    'trackRedisCommand create span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new QuerySpan('SELECT', ['exampleTable'], $parentTraceEvent, $date, $date);

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildRedisSpanFromExecuteEvent')
            ->once()
            ->withArgs([$parentTraceEvent, $commandEvent, $date])
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->trackRedisCommand($commandEvent, $date))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'trackRedisCommand create span and use current time with no given start at',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new QuerySpan('SELECT', ['exampleTable'], $parentTraceEvent, $date, $date);
        Carbon::setTestNow($date);

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildRedisSpanFromExecuteEvent')
            ->once()
            ->withArgs(fn (...$args) => $date->eq($args[2]))
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->trackRedisCommand($commandEvent))->toBe($span);
    }
)
    ->with('all possible child trace events');

test(
    'captureAction dont create plain span without current trace event but run callback',
    /**
     * @throws Throwable
     */
    function (): void {
        $name = fake()->word();
        $type = fake()->word();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes): void {
            ++$callbackExecuteTimes;
        };

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback))
            ->toBeNull()
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
);

test(
    'captureAction dont create plain span with completed current trace event but run callback',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     *
     * @throws Throwable
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = Carbon::now();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes): void {
            ++$callbackExecuteTimes;
        };

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback))
            ->toBeNull()
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
)
    ->with('all possible child trace events');

test(
    'captureAction create plain span but mapper build no span and callback is run once',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     *
     * @throws Throwable
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildPlainSpan')
            ->once()
            ->andReturnNull();

        $storeMock->allows('addSpan')->never();

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes): void {
            ++$callbackExecuteTimes;
        };

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback, $subType))
            ->toBeNull()
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
)
    ->with('all possible child trace events');

test(
    'captureAction create plain span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     *
     * @throws Throwable
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new PlainSpan($name, $type, $parentTraceEvent, $date);
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildPlainSpan')
            ->once()
            ->andReturn($span);

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes): void {
            ++$callbackExecuteTimes;
        };

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback, $subType))
            ->toBe($span)
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
)
    ->with('all possible child trace events');

test(
    'captureAction create plain span and use current time with no given start at',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     *
     * @throws Throwable
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new PlainSpan($name, $type, $parentTraceEvent, $date);
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildPlainSpan')
            ->once()
            ->withArgs(fn (...$args) => $date->eq($args[4]))
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes): void {
            ++$callbackExecuteTimes;
        };

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback, $subType))
            ->toBe($span)
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
)
    ->with('all possible child trace events');

test(
    'captureAction dont create system span without current trace event',
    /**
     * @throws Throwable
     */
    function (): void {
        $name = fake()->word();
        $type = fake()->word();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes): void {
            ++$callbackExecuteTimes;
        };

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback, system: true))
            ->toBeNull()
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
);

test(
    'captureAction dont create system span  with completed current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     *
     * @throws Throwable
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = Carbon::now();

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes): void {
            ++$callbackExecuteTimes;
        };

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback, system: true))
            ->toBeNull()
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
)
    ->with('all possible child trace events');

test(
    'captureAction create system span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     *
     * @throws Throwable
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildSystemSpan')
            ->once()
            ->andReturnNull();

        $storeMock->allows('addSpan')->never();

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes): void {
            ++$callbackExecuteTimes;
        };

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback, $subType, true))
            ->toBeNull()
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
)
    ->with('all possible child trace events');

test(
    'captureAction create system span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     *
     * @throws Throwable
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $date                       = new Carbon(fake()->dateTime());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new SystemSpan($name, $type, $parentTraceEvent, $date);
        Carbon::setTestNow($date);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildSystemSpan')
            ->once()
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes): void {
            ++$callbackExecuteTimes;
        };

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback, $subType, true))
            ->toBe($span)
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
)
    ->with('all possible child trace events');

test(
    'captureAction create system span and use current time for successful callback',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     *
     * @throws Throwable
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $startDate                  = new Carbon(fake()->dateTime());
        $finishDate                 = $startDate->clone()->addMinutes(fake()->randomDigit());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new SystemSpan($name, $type, $parentTraceEvent, $startDate);
        Carbon::setTestNow($startDate);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildSystemSpan')
            ->once()
            ->withArgs(fn (...$args) => $startDate->eq($args[4]))
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes, $finishDate): void {
            ++$callbackExecuteTimes;
            Carbon::setTestNow($finishDate);
        };

        $collector = new SpanCollector();
        expect($collector->captureAction($name, $type, $callback, $subType, system: true))
            ->toBe($span)
            ->and($span->finishAt)
            ->toEqual($finishDate)
            ->and($span->successful)
            ->toBeTrue()
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
)
    ->with('all possible child trace events');

test(
    'captureAction create system span and use current time for failing callback',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     *
     * @throws Throwable
     */
    function (Closure $buildTraceChild): void {
        $name                       = fake()->word();
        $type                       = fake()->word();
        $subType                    = fake()->word();
        $startDate                  = new Carbon(fake()->dateTime());
        $finishDate                 = $startDate->clone()->addMinutes(fake()->randomDigit());
        $parentTraceEvent           = $buildTraceChild();
        $parentTraceEvent->finishAt = null;
        $span                       = new SystemSpan($name, $type, $parentTraceEvent, $startDate);
        $expectedException          = new Exception(
            fake()->text(),
            fake()->numberBetween(1),
        );
        Carbon::setTestNow($startDate);

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        /** @var MapperContract&MockInterface $mapperMock */
        $mapperMock = Mockery::mock(MapperContract::class);
        App::bind(MapperContract::class, fn () => $mapperMock);

        /** @var ErrorCollectorContract&MockInterface $errorCollectorMock */
        $errorCollectorMock = Mockery::mock(ErrorCollectorContract::class);
        App::bind(ErrorCollectorContract::class, fn () => $errorCollectorMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($parentTraceEvent);

        $mapperMock->allows('buildSystemSpan')
            ->once()
            ->withArgs(fn (...$args) => $startDate->eq($args[4]))
            ->andReturn($span);

        $storeMock->allows('addSpan')->once()->withArgs([$span])->andReturnTrue();

        $errorCollectorMock
            ->allows('captureExceptionAsError')
            ->once()
            ->withArgs([$expectedException]);

        $callbackExecuteTimes = 0;
        $callback             = function () use (&$callbackExecuteTimes, $finishDate, $expectedException): void {
            ++$callbackExecuteTimes;
            Carbon::setTestNow($finishDate);

            throw $expectedException;
        };

        $collector = new SpanCollector();

        $exception = null;

        try {
            $collector->captureAction($name, $type, $callback, $subType, system: true);
        } catch (Throwable $e) {
            $exception = $e;
        }

        expect($exception)
            ->toBe($expectedException)
            ->and($span->finishAt)
            ->toEqual($finishDate)
            ->and($span->successful)
            ->toBeFalse()
            ->and($callbackExecuteTimes)
            ->toBe(1);
    }
)
    ->with('all possible child trace events');
