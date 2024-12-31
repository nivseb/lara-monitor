<?php

namespace Tests\Component\Collectors;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\App;
use Nivseb\LaraMonitor\Collectors\SpanCollector;
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
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\RequestInterface;

test(
    'startAction dont create plain span without current trace event',
    function (): void {
        $name = fake()->word();
        $type = fake()->word();

        /** @var RepositoryContract&MockInterface $storeMock */
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
    'startAction create plain span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $name             = fake()->word();
        $type             = fake()->word();
        $subType          = fake()->word();
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $name             = fake()->word();
        $type             = fake()->word();
        $subType          = fake()->word();
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new PlainSpan($name, $type, $parentTraceEvent, $date);

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $name             = fake()->word();
        $type             = fake()->word();
        $subType          = fake()->word();
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new PlainSpan($name, $type, $parentTraceEvent, $date);
        Carbon::setTestNow($date);

        /** @var RepositoryContract&MockInterface $storeMock */
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

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $name             = fake()->word();
        $type             = fake()->word();
        $subType          = fake()->word();
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $name             = fake()->word();
        $type             = fake()->word();
        $subType          = fake()->word();
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new SystemSpan($name, $type, $parentTraceEvent, $date);

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $name             = fake()->word();
        $type             = fake()->word();
        $subType          = fake()->word();
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new SystemSpan($name, $type, $parentTraceEvent, $date);
        Carbon::setTestNow($date);

        /** @var RepositoryContract&MockInterface $storeMock */
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
    'startHttpAction dont create span without current trace event',
    function (): void {
        /** @var MockInterface&RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();

        /** @var MockInterface&RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new HttpSpan('GET', '/', $parentTraceEvent, Carbon::now());

        /** @var MockInterface&RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new HttpSpan('GET', '/', $parentTraceEvent, Carbon::now());
        Carbon::setTestNow($date);

        /** @var MockInterface&RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        /** @var RepositoryContract&MockInterface $storeMock */
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

        /** @var RepositoryContract&MockInterface $storeMock */
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
    'startRenderAction create span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $response         = fake()->text();

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $response         = fake()->text();
        $span             = new RenderSpan('', $parentTraceEvent, Carbon::now());

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $response         = fake()->text();
        $span             = new RenderSpan('', $parentTraceEvent, Carbon::now());
        Carbon::setTestNow($date);

        /** @var RepositoryContract&MockInterface $storeMock */
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
        /** @var RepositoryContract&MockInterface $storeMock */
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

        /** @var RepositoryContract&MockInterface $storeMock */
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

        /** @var RepositoryContract&MockInterface $storeMock */
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

        /** @var RepositoryContract&MockInterface $storeMock */
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

        /** @var RepositoryContract&MockInterface $storeMock */
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
    'trackDatabaseQuery create span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new QuerySpan('SELECT', ['exampleTable'], $parentTraceEvent, $date, $date);

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new QuerySpan('SELECT', ['exampleTable'], $parentTraceEvent, $date, $date);
        Carbon::setTestNow($date);

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var RepositoryContract&MockInterface $storeMock */
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

        /** @var RepositoryContract&MockInterface $storeMock */
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
    'trackRedisCommand create span but mapper build no span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new QuerySpan('SELECT', ['exampleTable'], $parentTraceEvent, $date, $date);

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var RepositoryContract&MockInterface $storeMock */
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
        $date             = new Carbon(fake()->dateTime());
        $parentTraceEvent = $buildTraceChild();
        $span             = new QuerySpan('SELECT', ['exampleTable'], $parentTraceEvent, $date, $date);
        Carbon::setTestNow($date);

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var RepositoryContract&MockInterface $storeMock */
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
