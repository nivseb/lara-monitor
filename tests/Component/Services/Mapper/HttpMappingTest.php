<?php

namespace Tests\Component\Services\Mapper;

use Carbon\Carbon;
use Closure;
use GuzzleHttp\Psr7\Request;
use Nivseb\LaraMonitor\Services\Mapper;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

test(
    'span is build as http span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $request    = new Request('GET', '/');

        $mapper = new Mapper();
        $span   = $mapper->buildHttpSpanFromRequest($traceEvent, $request, Carbon::now());

        expect($span)->toBeInstanceOf(HttpSpan::class);
    }
)
    ->with('all possible child trace events');

test(
    'span get correct trace parent',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $request    = new Request('GET', '/');

        $mapper = new Mapper();

        /** @var HttpSpan $span */
        $span = $mapper->buildHttpSpanFromRequest($traceEvent, $request, Carbon::now());

        expect($span->parentEvent)->toBe($traceEvent);
    }
)
    ->with('all possible child trace events');

test(
    'span get correct host',
    function (string $uri, string $expectedHost): void {
        $request = new Request('GET', $uri);
        $mapper  = new Mapper();

        /** @var HttpSpan $span */
        $span = $mapper->buildHttpSpanFromRequest(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $request,
            Carbon::now()
        );

        expect($span->host)->toBe($expectedHost);
    }
)
    ->with(
        [
            'localhost'       => ['https://localhost/test', 'localhost'],
            'external domain' => ['https://github.com/test', 'github.com'],
            'ip address'      => ['https://192.168.0.1/test', '192.168.0.1'],
        ]
    );

test(
    'span get correct scheme',
    function (string $uri, string $expectedScheme): void {
        $request = new Request('GET', $uri);
        $mapper  = new Mapper();

        /** @var HttpSpan $span */
        $span = $mapper->buildHttpSpanFromRequest(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $request,
            Carbon::now()
        );

        expect($span->scheme)->toBe($expectedScheme);
    }
)
    ->with(
        [
            'http scheme'     => ['http://localhost/test', 'http'],
            'https scheme'    => ['https://localhost/test', 'https'],
            'no given scheme' => ['localhost/test', 'http'],
        ]
    );

test(
    'span get correct port',
    function (string $uri, int $expectedPort): void {
        $request = new Request('GET', $uri);
        $mapper  = new Mapper();

        /** @var HttpSpan $span */
        $span = $mapper->buildHttpSpanFromRequest(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $request,
            Carbon::now()
        );

        expect($span->port)->toBe($expectedPort);
    }
)
    ->with(
        [
            'default http'     => ['http://localhost/test', 80],
            'given http port'  => ['https://localhost:80/test', 80],
            'default https'    => ['https://localhost/test', 443],
            'given https port' => ['http://localhost:443/test', 443],
            'other port'       => ['https://localhost:123/test', 123],
        ]
    );

test(
    'span get correct path',
    function (string $uri, string $expectedPath): void {
        $request = new Request('GET', $uri);
        $mapper  = new Mapper();

        /** @var HttpSpan $span */
        $span = $mapper->buildHttpSpanFromRequest(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $request,
            Carbon::now()
        );

        expect($span->path)->toBe($expectedPath);
    }
)
    ->with(
        [
            'no path'                               => ['http://localhost', '/'],
            'no path with query parameter'          => ['http://localhost?test=1', '/'],
            'root'                                  => ['http://localhost/', '/'],
            'root with query parameter'             => ['http://localhost/?test=1', '/'],
            'single directory'                      => ['https://localhost/test', '/test'],
            'single directory with query parameter' => [
                'https://localhost/test?test=1',
                '/test'],
            'multi directory' => [
                'https://localhost/test/subDirectory/Another',
                '/test/subDirectory/Another',
            ],
            'multi directory with query parameter' => [
                'https://localhost/test/subDirectory/Another?test=1',
                '/test/subDirectory/Another',
            ],
            'file' => [
                'https://localhost/test/ThatIsMyFile.txt',
                '/test/ThatIsMyFile.txt',
            ],
            'file with query parameter' => [
                'https://localhost/test/ThatIsMyFile.txt?test=1', '/test/ThatIsMyFile.txt',
            ],
        ]
    );
