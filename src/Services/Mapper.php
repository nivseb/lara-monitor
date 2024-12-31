<?php

namespace Nivseb\LaraMonitor\Services;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Arr;
use Nivseb\LaraMonitor\Contracts\MapperContract;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\PlainSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Spans\RenderSpan;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\User;
use Jasny\Persist\SQL\Query;
use Jasny\Persist\SQL\Query\UnsupportedQueryException;
use Psr\Http\Message\RequestInterface;
use ReflectionProperty;

class Mapper implements MapperContract
{
    public function buildUserFromAuthenticated(string $guard, Authenticatable $user): ?User
    {
        $email = null;
        if (is_a($user, Model::class)) {
            $email = $user->getAttribute('email');
        } elseif (property_exists($user, 'email')) {
            $property = new ReflectionProperty($user, 'email');
            if ($property->isPublic()) {
                $email = $user->email;
            }
        }

        return new User(
            $guard,
            $user->getAuthIdentifier(),
            $email,
            $email,
        );
    }

    public function buildPlainSpan(
        AbstractChildTraceEvent $parentTraceEvent,
        string $name,
        string $type,
        ?string $subType,
        CarbonInterface $startAt
    ): ?AbstractSpan {
        return new PlainSpan($name, $type, $parentTraceEvent, $startAt, $subType);
    }

    public function buildSystemSpan(
        AbstractChildTraceEvent $parentTraceEvent,
        string $name,
        string $type,
        ?string $subType,
        CarbonInterface $startAt
    ): ?AbstractSpan {
        return new SystemSpan($name, $type, $parentTraceEvent, $startAt, $subType);
    }

    public function buildHttpSpanFromRequest(
        AbstractChildTraceEvent $parentTraceEvent,
        RequestInterface $request,
        CarbonInterface $startAt
    ): ?AbstractSpan {
        $uri  = $request->getUri();
        $path = $uri->getPath();
        $span = new HttpSpan(
            $request->getMethod(),
            !$path ? '/' : $path,
            $parentTraceEvent,
            $startAt
        );

        $span->host   = $uri->getHost();
        $span->scheme = $uri->getScheme();
        if (!$span->scheme) {
            $span->scheme = 'http';
        }
        $span->port = $uri->getPort();
        if (!$span->port) {
            $span->port = $uri->getScheme() === 'https' ? 443 : 80;
        }

        return $span;
    }

    public function buildRenderSpanForResponse(
        AbstractChildTraceEvent $parentTraceEvent,
        mixed $response,
        CarbonInterface $startAt
    ): ?AbstractSpan {
        return new RenderSpan(
            $this->mapRenderResponseType($response),
            $parentTraceEvent,
            $startAt
        );
    }

    public function buildQuerySpanFromExecuteEvent(
        AbstractChildTraceEvent $parentTraceEvent,
        QueryExecuted $event,
        CarbonInterface $finishAt
    ): ?AbstractSpan {
        $queryType = 'Unknown';
        $tables    = [];

        try {
            if ($event->sql) {
                $query     = new Query($event->sql);
                $queryType = $query->getType() ?? $queryType;
                $tables    = array_values($query->getTables());
            }
        } catch (UnsupportedQueryException) {
        }

        $span = new QuerySpan(
            $queryType,
            $tables,
            $parentTraceEvent,
            $this->calcStartDate($finishAt, (float) $event->time),
            $finishAt
        );

        $span->connectionName = $event->connectionName ?: 'default';
        $span->databaseType   = $this->getDatabaseType($event->connection);
        $span->sqlStatement   = $event->sql;
        $span->bindings       = $event->bindings;
        $span->host           = $event->connection->getConfig('host') ?? 'missing';
        $span->port           = $event->connection->getConfig('port');

        return $span;
    }

    public function buildRedisSpanFromExecuteEvent(
        AbstractChildTraceEvent $parentTraceEvent,
        CommandExecuted $event,
        CarbonInterface $finishAt
    ): ?AbstractSpan {
        $statement = match ($event->command) {
            'eval'  => Arr::first($event->parameters, default: $event->command),
            default => $event->command
        };
        $client = $event->connection->client();

        $span = new RedisCommandSpan(
            $event->command,
            $statement ?: 'Unknown',
            $parentTraceEvent,
            $this->calcStartDate($finishAt, (float) $event->time),
            $finishAt
        );

        $span->parameters     = $event->parameters;
        $span->connectionName = $event->connectionName ?: 'default';
        $span->host           = 'missing';
        $span->port           = null;
        if (is_a($client, 'Redis')) {
            $span->host = $client->getHost() ?: 'missing';
            $span->port = $client->getPort();
        }

        return $span;
    }

    protected function mapRenderResponseType(mixed $response): string
    {
        return match (true) {
            $response instanceof View         => 'view',
            $response instanceof JsonResource => 'resource',
            $response instanceof JsonResponse => 'json',
            $response instanceof Response     => 'response',
            default                           => 'other'
        };
    }

    protected function calcStartDate(CarbonInterface $startDate, float $runtime): CarbonInterface
    {
        return $startDate
            ->clone()
            ->subMicroseconds((int) ($runtime * CarbonInterface::MICROSECONDS_PER_MILLISECOND));
    }

    protected function getDatabaseType(Connection $connection): string
    {
        return match (true) {
            $connection instanceof MySqlConnection     => $connection->isMaria() ? 'mariadb' : 'mysql',
            $connection instanceof SqlServerConnection => 'mssql',
            $connection instanceof SQLiteConnection    => 'sqlite',
            $connection instanceof PostgresConnection  => 'postgresql',
            default                                    => $connection->getDriverName(),
        };
    }
}
