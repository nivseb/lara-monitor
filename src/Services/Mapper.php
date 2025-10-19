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
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Queue\Jobs\JobName;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Nivseb\LaraMonitor\Contracts\MapperContract;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\JobQueueingSpan;
use Nivseb\LaraMonitor\Struct\Spans\PlainSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Spans\RenderSpan;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\User;
use Nivseb\LaraMonitor\Traits\HasLogging;
use PDO;
use Psr\Http\Message\RequestInterface;
use ReflectionProperty;
use Throwable;

class Mapper implements MapperContract
{
    use HasLogging;

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
        return new HttpSpan(
            $request->getMethod(),
            $request->getUri(),
            $parentTraceEvent,
            $startAt
        );
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
        $queryType = '';
        $tables    = [];
        $sql       = preg_replace('/(--.*\n)/', '', $event->sql);
        $sql       = preg_replace('/(\/\*.*\*\/)/', '', $sql);
        $sql       = preg_replace('/\(\s*SELECT\s+.*?\s+FROM\s+.*?\)/is', ' ', $sql);

        if (preg_match('/^([a-z]+)(.*)/i', $sql, $matches)) {
            $queryType = strtoupper($matches[1]);
        }

        switch ($queryType) {
            case 'SELECT':
            case 'DELETE':
                if (preg_match('/FROM [`"\[]?([^\s`"(\]]+)[`"\[]?/i', $sql, $matches)) {
                    $tables = explode(',', $matches[1]);
                }

                break;

            case 'UPDATE':
                if (preg_match('/UPDATE (LOW_PRIORITY)?\s?(IGNORE|ONLY)?\s?[`"\[]?([a-z][^\s`"\]]+)[`"\[]?/i', $sql, $matches)) {
                    $tables = explode(',', $matches[3]);
                }

                break;

            case 'INSERT':
                if (preg_match('/INTO [`"\[]?([^\s`"\]]+)[`"\[]?/i', $sql, $matches)) {
                    $tables = explode(',', $matches[1]);
                }

                break;

            case 'CALL':
                if (preg_match('/CALL ([^\s(]+)\(?/i', $sql, $matches)) {
                    $tables = explode(',', $matches[1]);
                }

                break;
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
        $span->host           = $this->getDatabaseHost($event->connection);
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

    public function buildJobQueueingSpan(
        AbstractChildTraceEvent $parentTraceEvent,
        JobQueueing $event,
        CarbonInterface $startAt
    ): ?AbstractSpan {
        try {
            return new JobQueueingSpan(
                JobName::resolve('Unknown Job', $event->payload()),
                $parentTraceEvent,
                $startAt
            );
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Fail build job queueing span!', $exception);

            return null;
        }
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

    protected function getDatabaseHost(Connection $connection): string
    {
        if (!extension_loaded('pdo')) {
            return 'missing';
        }

        try {
            $pdo = $connection->getRawPdo();
            if ($pdo) {
                $connectionString = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);

                return Str::before($connectionString, ' ');
            }
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t detect host from pdo.', $exception);
        }
        $configHost = $connection->getConfig('host');

        return is_string($configHost) ? $configHost : 'missing';
    }
}
