<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Http\TraceParentMiddleware;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Psr\Http\Message\RequestInterface;

test(
    'dont add header without current trace event',
    function (): void {
        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturnNull();

        $method             = fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']);
        $request            = new Request($method, '/');
        $middlewareInstance = new TraceParentMiddleware();
        $expectResponse     = new Response();
        $handler            = function (RequestInterface $request) use ($expectResponse) {
            expect($request->getHeaders())->toBe([]);

            return $expectResponse;
        };

        $middlewareClosure = $middlewareInstance($handler);
        $givenResponse     = $middlewareClosure($request, []);

        expect($givenResponse)->toBe($expectResponse);
    }
);

test(
    'add header with correct trace parent from current trace event',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $traceEvent->finishAt = null;

        /** @var MockInterface&RepositoryContract $storeMock */
        $storeMock = Mockery::mock(RepositoryContract::class);
        App::bind(RepositoryContract::class, fn () => $storeMock);

        $storeMock->allows('getCurrentTraceEvent')->once()->withNoArgs()->andReturn($traceEvent);

        $method             = fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']);
        $request            = new Request($method, '/');
        $middlewareInstance = new TraceParentMiddleware();
        $expectResponse     = new Response();
        $handler            = function (RequestInterface $request) use ($expectResponse, $traceEvent) {
            expect($request->getHeaders())->toBe(['traceparent' => [(string) $traceEvent->asW3CTraceParent()]]);

            return $expectResponse;
        };

        $middlewareClosure = $middlewareInstance($handler);
        $givenResponse     = $middlewareClosure($request, []);

        expect($givenResponse)->toBe($expectResponse);
    }
)
    ->with('all possible child trace events');
