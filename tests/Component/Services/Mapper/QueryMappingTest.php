<?php

namespace Tests\Component\Services\Mapper;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Services\Mapper;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;
use PDO;

test(
    'span is build as query span',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->twice()->andReturnNull();

        $mapper = new Mapper();
        $span   = $mapper->buildQuerySpanFromExecuteEvent($traceEvent, $queryEvent, Carbon::now());

        expect($span)->toBeInstanceOf(QuerySpan::class);
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

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->twice()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent($traceEvent, $queryEvent, Carbon::now());

        expect($span->parentEvent)->toBe($traceEvent);
    }
)
    ->with('all possible child trace events');

test(
    'span get correct type and tables',
    function (string $sqlStatement, string $expectedQueryType, array $expectedTables): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = $sqlStatement;
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->twice()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
            Carbon::now()
        );

        expect($span->queryType)->toBe($expectedQueryType)
            ->and($span->tables)->toBe($expectedTables);
    }
)
    ->with('possible sql queries');

test(
    'span receive given date as end time',
    /**
     * @param Closure() : AbstractChildTraceEvent $buildTraceChild
     */
    function (Closure $buildTraceChild): void {
        $traceEvent = $buildTraceChild();
        $date       = new Carbon(fake()->dateTime());
        $time       = (int) $date->format('Uu');

        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->twice()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent($traceEvent, $queryEvent, $date);

        expect($span->finishAt)->toBe($time);
    }
)
    ->with('all possible child trace events');

test(
    'start time is calculated correct',
    function (float $duration, CarbonInterface $finishAt, CarbonInterface $expectedStartAt): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->twice()->andReturnNull();
        $queryEvent->time = $duration;

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
            $finishAt
        );

        expect($span->startAt)->toBe((int) $expectedStartAt->format('Uu'));
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
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent                 = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql            = '';
        $queryEvent->bindings       = [];
        $queryEvent->connectionName = $givenName;

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->twice()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
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
    'get correct host from config',
    function (array|string|null $givenConfigHost, string $expectedHost): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getRawPdo')->withNoArgs()->once()->andReturnNull();
        $connectionMock->allows('getConfig')->withArgs(['host'])->once()->andReturn($givenConfigHost);
        $connectionMock->allows('getConfig')->withArgs(['port'])->once()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
            Carbon::now()
        );

        expect($span->host)->toBe($expectedHost);
    }
)
    ->with(
        [
            'null'               => [null, 'missing'],
            'hostname'           => ['localhost', 'localhost'],
            'domain'             => ['external.test.com', 'external.test.com'],
            'ip address'         => ['127.0.0.1', '127.0.0.1'],
            'multiple hostnames' => [['host1', 'host2'], 'missing'],
            'multiple ips'       => [['127.0.0.1', '172.17.0.1'], 'missing'],
        ]
    );

test(
    'get correct host from pdo',
    function (string $connectionStatus, string $expectedHost): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var MockInterface&PDO $connectionMock */
        $pdoMock = Mockery::mock(PDO::class);
        $pdoMock
            ->allows('getAttribute')
            ->withArgs([PDO::ATTR_CONNECTION_STATUS])
            ->andReturn($connectionStatus);

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getRawPdo')->withNoArgs()->once()->andReturn($pdoMock);
        $connectionMock->allows('getConfig')->never();
        $connectionMock->allows('getConfig')->withArgs(['port'])->once()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
            Carbon::now()
        );

        expect($span->host)->toBe($expectedHost);
    }
)
    ->with(
        [
            'hostname'   => ['localhost via TCP/IP', 'localhost'],
            'domain'     => ['external.test.com via TCP/IP', 'external.test.com'],
            'ip address' => ['127.0.0.1 via TCP/IP', '127.0.0.1'],
        ]
    );

test(
    'get correct port',
    function (int|string|null $givenPort, ?int $expectedPort): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->withArgs(['host'])->once()->andReturnNull();
        $connectionMock->allows('getConfig')->withArgs(['port'])->once()->andReturn($givenPort);

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
            Carbon::now()
        );

        expect($span->port)->toBe($expectedPort);
    }
)
    ->with(
        [
            'null'    => [null, null],
            'integer' => [3306, 3306],
            'string'  => ['3306', 3306],
        ]
    );

test(
    'map sql statement',
    function (string $sqlStatement): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = $sqlStatement;
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->twice()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
            Carbon::now()
        );

        expect($span->sqlStatement)->toBe($sqlStatement);
    }
)
    ->with('possible sql queries');

test(
    'map sql bindings',
    function (array $bindings): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = 'SELECT * FROM exampleTable';
        $queryEvent->bindings = $bindings;

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->twice()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
            Carbon::now()
        );

        expect($span->bindings)->toBe($bindings);
    }
)
    ->with(
        [
            'no bindings'     => [[]],
            'simple bindings' => [['example Name', 12345, 24.8]],
        ]
    );

test(
    'map database type based on connection',
    function (callable $connectionBuilder, string $expectedDataBaseType): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = '';
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = $connectionBuilder();
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getConfig')->twice()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
            Carbon::now()
        );

        expect($span->databaseType)->toBe($expectedDataBaseType);
    }
)
    ->with(
        [
            'mysql' => [
                function () {
                    $connection = Mockery::mock(MySqlConnection::class);
                    $connection->allows('isMaria')->once()->andReturn(false);

                    return $connection;
                },
                'mysql',
            ],
            'mariadb' => [
                function () {
                    $connection = Mockery::mock(MySqlConnection::class);
                    $connection->allows('isMaria')->once()->andReturn(true);

                    return $connection;
                },
                'mariadb',
            ],
            'mssql'       => [fn () => Mockery::mock(SqlServerConnection::class), 'mssql'],
            'sqlite'      => [fn () => Mockery::mock(SQLiteConnection::class), 'sqlite'],
            'postgresql'  => [fn () => Mockery::mock(PostgresConnection::class), 'postgresql'],
            'driver name' => [
                function () {
                    $connection = Mockery::mock(Connection::class);
                    $connection->allows('getDriverName')->once()->andReturn('myDriver');

                    return $connection;
                },
                'myDriver',
            ],
        ]
    );

test(
    'map database to correct name from elastic apm examples',
    function (string $sqlInput, string $expectedOutput): void {
        /** @var MockInterface&QueryExecuted $queryEvent */
        $queryEvent           = Mockery::mock(QueryExecuted::class);
        $queryEvent->sql      = $sqlInput;
        $queryEvent->bindings = [];

        /** @var Connection&MockInterface $connectionMock */
        $connectionMock         = Mockery::mock(Connection::class);
        $queryEvent->connection = $connectionMock;
        $connectionMock->allows('getDriverName')->once()->andReturn('mysql');
        $connectionMock->allows('getConfig')->twice()->andReturnNull();

        $mapper = new Mapper();

        /** @var QuerySpan $span */
        $span = $mapper->buildQuerySpanFromExecuteEvent(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $queryEvent,
            Carbon::now()
        );

        expect($span->getName())->toBe($expectedOutput);
    }
)
    ->with('elastic apm sql mapping');
