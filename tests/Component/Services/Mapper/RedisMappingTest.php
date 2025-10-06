<?php

namespace Tests\Component\Services\Mapper;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Events\CommandExecuted;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Services\Mapper;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;
use Redis;

test(
    'span is build as redis span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock           = Mockery::mock(Connection::class);
        $commandEvent->connection = $connectionMock;
        $connectionMock->allows('client')->once()->andReturnNull();

        $mapper = new Mapper();
        $span   = $mapper->buildRedisSpanFromExecuteEvent($traceEvent, $commandEvent, Carbon::now());

        expect($span)->toBeInstanceOf(RedisCommandSpan::class);
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

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock           = Mockery::mock(Connection::class);
        $commandEvent->connection = $connectionMock;
        $connectionMock->allows('client')->once()->andReturnNull();

        $mapper = new Mapper();

        /** @var RedisCommandSpan $span */
        $span = $mapper->buildRedisSpanFromExecuteEvent($traceEvent, $commandEvent, Carbon::now());

        expect($span->parentEvent)->toBe($traceEvent);
    }
)
    ->with('all possible child trace events');

test(
    'span receive given date as end time',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent   = $buildTraceChild();
        $expectedDate = new Carbon(fake()->dateTime());

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock           = Mockery::mock(Connection::class);
        $commandEvent->connection = $connectionMock;
        $connectionMock->allows('client')->once()->andReturnNull();

        $mapper = new Mapper();

        /** @var RedisCommandSpan $span */
        $span = $mapper->buildRedisSpanFromExecuteEvent($traceEvent, $commandEvent, $expectedDate);

        expect($span->finishAt)->toBe($expectedDate);
    }
)
    ->with('all possible child trace events');

test(
    'start time is calculated correct',
    function (float $duration, CarbonInterface $finishAt, CarbonInterface $expectedStartAt): void {
        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock           = Mockery::mock(Connection::class);
        $commandEvent->connection = $connectionMock;
        $connectionMock->allows('client')->once()->andReturnNull();
        $commandEvent->time = $duration;

        $mapper = new Mapper();

        /** @var RedisCommandSpan $span */
        $span = $mapper->buildRedisSpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $commandEvent,
            $finishAt
        );

        expect($span->startAt?->format('Uu'))->toBe($expectedStartAt->format('Uu'));
    }
)
    ->with(
        [
            'one second' => [
                1000.0,
                new Carbon('2024-12-21 14:36:54.543'),
                new Carbon('2024-12-21 14:36:53.543'),
            ],
            'one millisecond' => [
                1.0,
                new Carbon('2024-12-21 14:36:54.543'),
                new Carbon('2024-12-21 14:36:54.542'),
            ],
            'one microsecond' => [
                0.001,
                new Carbon('2024-12-21 14:36:54.543'),
                Carbon::parse('2024-12-21 14:36:54.543')->subMicrosecond(),
            ],
            'ten second' => [
                10000.0,
                new Carbon('2024-12-21 14:36:54.543'),
                new Carbon('2024-12-21 14:36:44.543'),
            ],
            'one minute second' => [
                60000.0,
                new Carbon('2024-12-21 14:36:54.543'),
                new Carbon('2024-12-21 14:35:54.543'),
            ],
        ]
    );

test(
    'get correct connection name',
    function (string $givenName, string $expectedConnectionName): void {
        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent                 = Mockery::mock(CommandExecuted::class);
        $commandEvent->command        = '';
        $commandEvent->parameters     = [];
        $commandEvent->connectionName = $givenName;

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock           = Mockery::mock(Connection::class);
        $commandEvent->connection = $connectionMock;
        $connectionMock->allows('client')->once()->andReturnNull();

        $mapper = new Mapper();

        /** @var RedisCommandSpan $span */
        $span = $mapper->buildRedisSpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $commandEvent,
            Carbon::now()
        );

        expect($span->connectionName)->toBe($expectedConnectionName);
    }
)
    ->with(
        [
            'empty string' => ['', 'default'],
            'given name'   => ['myConnection', 'myConnection'],
        ]
    );

test(
    'get correct host',
    function (?string $givenHost, string $expectedHost): void {
        /** @var MockInterface&Redis $clientMock */
        $clientMock = Mockery::mock(Redis::class);
        $clientMock->allows('getHost')->once()->withNoArgs()->andReturn($givenHost);
        $clientMock->allows('getPort')->once()->withNoArgs()->andReturn(0);

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock           = Mockery::mock(Connection::class);
        $commandEvent->connection = $connectionMock;
        $connectionMock->allows('client')->once()->andReturn($clientMock);

        $mapper = new Mapper();

        /** @var RedisCommandSpan $span */
        $span = $mapper->buildRedisSpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $commandEvent,
            Carbon::now()
        );

        expect($span->host)->toBe($expectedHost);
    }
)
    ->with(
        [
            'empty'      => ['', 'missing'],
            'hostname'   => ['localhost', 'localhost'],
            'domain'     => ['external.test.com', 'external.test.com'],
            'ip address' => ['127.0.0.1', '127.0.0.1'],
        ]
    );

test(
    'get correct port',
    function (int|string|null $givenPort, ?int $expectedPort): void {
        /** @var MockInterface&Redis $clientMock */
        $clientMock = Mockery::mock(Redis::class);
        $clientMock->allows('getHost')->once()->withNoArgs()->andReturn('');
        $clientMock->allows('getPort')->once()->withNoArgs()->andReturn($givenPort);

        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = '';
        $commandEvent->parameters = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock           = Mockery::mock(Connection::class);
        $commandEvent->connection = $connectionMock;
        $connectionMock->allows('client')->once()->andReturn($clientMock);

        $mapper = new Mapper();

        /** @var RedisCommandSpan $span */
        $span = $mapper->buildRedisSpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $commandEvent,
            Carbon::now()
        );

        expect($span->port)->toBe($expectedPort);
    }
)
    ->with(
        [
            'integer' => [6379, 6379],
            'string'  => ['6379', 6379],
        ]
    );

test(
    'map statement into span',
    function (string $redisCommand, array $parameters, string $expectedCommand): void {
        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = $redisCommand;
        $commandEvent->parameters = $parameters;

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock           = Mockery::mock(Connection::class);
        $commandEvent->connection = $connectionMock;
        $connectionMock->allows('client')->once()->andReturnNull();

        $mapper = new Mapper();

        /** @var RedisCommandSpan $span */
        $span = $mapper->buildRedisSpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $commandEvent,
            Carbon::now()
        );

        expect($span->statement)->toBe($expectedCommand);
    }
)
    ->with('possible redis commands');

test(
    'map parameters',
    function (string $redisCommand, array $parameters): void {
        /** @var CommandExecuted&MockInterface $commandEvent */
        $commandEvent             = Mockery::mock(CommandExecuted::class);
        $commandEvent->command    = $redisCommand;
        $commandEvent->parameters = $parameters;

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock           = Mockery::mock(Connection::class);
        $commandEvent->connection = $connectionMock;
        $connectionMock->allows('client')->once()->andReturnNull();

        $mapper = new Mapper();

        /** @var RedisCommandSpan $span */
        $span = $mapper->buildRedisSpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $commandEvent,
            Carbon::now()
        );

        expect($span->parameters)->toBe($parameters);
    }
)
    ->with('possible redis commands');
