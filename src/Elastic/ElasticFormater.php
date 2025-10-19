<?php

namespace Nivseb\LaraMonitor\Elastic;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Enums\Elastic\Outcome;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Elastic\TypeData;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\JobQueueingSpan;
use Nivseb\LaraMonitor\Struct\Spans\PlainSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Spans\RenderSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

class ElasticFormater implements ElasticFormaterContract
{
    public function getSpanTypeData(AbstractSpan $span): ?TypeData
    {
        return match (true) {
            $span instanceof QuerySpan        => new TypeData('db', $span->databaseType, 'query'),
            $span instanceof RedisCommandSpan => new TypeData('db', 'redis', 'query'),
            $span instanceof HttpSpan         => new TypeData('external', 'http'),
            $span instanceof RenderSpan       => new TypeData('template', $span->type, 'render'),
            $span instanceof JobQueueingSpan  => new TypeData('queue', 'dispatch'),
            $span instanceof PlainSpan        => new TypeData($span->type, $span->subType),
            default                           => null,
        };
    }

    public function getTransactionType(AbstractTransaction $transaction): string
    {
        return match (true) {
            $transaction instanceof RequestTransaction => 'request',
            $transaction instanceof CommandTransaction => 'command',
            $transaction instanceof JobTransaction     => 'job',
            default                                    => 'unknown'
        };
    }

    public function getOutcome(AbstractChildTraceEvent $traceEvent): ?Outcome
    {
        return match ($traceEvent->successful) {
            null  => null,
            true  => Outcome::Success,
            false => Outcome::Failure,
        };
    }

    public function getTimestamp(?CarbonInterface $date): ?int
    {
        if (!$date) {
            return null;
        }

        return (int) $date->format('Uu');
    }

    public function calcDuration(?CarbonInterface $startDate, ?CarbonInterface $endDate): ?float
    {
        if (!$startDate || !$endDate) {
            return null;
        }

        // Carbon method round for milliseconds
        return $startDate->diffInMicroseconds($endDate) / CarbonInterface::MICROSECONDS_PER_MILLISECOND;
    }
}
