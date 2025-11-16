<?php

namespace Tests\Datasets;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use GuzzleHttp\Psr7\Uri;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\JobQueueingSpan;
use Nivseb\LaraMonitor\Struct\Spans\PlainSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Spans\RenderSpan;
use Nivseb\LaraMonitor\Struct\Spans\SystemSpan;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Tracing\ExternalTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\OctaneRequestTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

dataset(
    'all trace events',
    [
        'unsampled w3c trace header' => fn () => new ExternalTrace(
            new W3CTraceParent(
                '00',
                bin2hex(random_bytes(16)),
                bin2hex(random_bytes(8)),
                '00'
            )
        ),
        'sampled w3c trace header' => fn () => new ExternalTrace(
            new W3CTraceParent(
                '00',
                bin2hex(random_bytes(16)),
                bin2hex(random_bytes(8)),
                '01'
            )
        ),
        'unsampled start trace with no sample rate' => fn () => new StartTrace(false, 0.00),
        'unsampled start trace with sample rate'    => fn () => new StartTrace(false, 1.00),
        'sampled start trace with no sample rate'   => fn () => new StartTrace(true, 0.00),
        'sampled start trace with sample rate'      => fn () => new StartTrace(true, 1.00),
    ]
);

dataset(
    'own trace events',
    [
        'unsampled start trace with no sample rate' => fn () => new StartTrace(false, 0.00),
        'unsampled start trace with sample rate'    => fn () => new StartTrace(false, 1.00),
        'sampled start trace with no sample rate'   => fn () => new StartTrace(true, 0.00),
        'sampled start trace with sample rate'      => fn () => new StartTrace(true, 1.00),
    ]
);

dataset(
    'external parents',
    [
        'unsampled w3c trace header' => fn () => new ExternalTrace(
            new W3CTraceParent(
                '00',
                bin2hex(random_bytes(16)),
                bin2hex(random_bytes(8)),
                '00'
            )
        ),
        'sampled w3c trace header' => fn () => new ExternalTrace(
            new W3CTraceParent(
                '00',
                bin2hex(random_bytes(16)),
                bin2hex(random_bytes(8)),
                '01'
            )
        ),
    ]
);

dataset(
    'all possible child trace events',
    [
        'reuqest transaction' => [fn () => new RequestTransaction(new StartTrace(false, 0.0), Carbon::now()->format('Uu')),
        ],
        'octane reuqest transaction' => [fn () => new OctaneRequestTransaction(new StartTrace(false, 0.0), Carbon::now()->format('Uu')),
        ],
        'command transaction' => [fn () => new CommandTransaction(new StartTrace(false, 0.0), Carbon::now()->format('Uu')),
        ],
        'job transaction' => [fn () => new JobTransaction(new StartTrace(false, 0.0), Carbon::now()->format('Uu')),
        ],
        'system span' => [
            fn () => new SystemSpan(
                'test',
                'test',
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()->format('Uu')
            ),
        ],
        'plain span' => [
            fn () => new PlainSpan(
                'test',
                'test',
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()->format('Uu')
            ),
        ],
        'query span' => [
            fn () => new QuerySpan(
                'SELECT',
                ['exampleTable'],
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()->format('Uu'),
                Carbon::now()->format('Uu'),
            ),
        ],
        'redis span' => [
            fn () => new RedisCommandSpan(
                'command',
                'statement',
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()->format('Uu'),
                Carbon::now()->format('Uu'),
            ),
        ],
        'http span' => [
            fn () => new HttpSpan(
                'GET',
                new Uri('/'),
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()->format('Uu')
            ),
        ],
        'render span' => [
            fn () => new RenderSpan(
                'test',
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()->format('Uu'),
            ),
        ],
        'job queueing span' => [
            fn () => new JobQueueingSpan(
                'test',
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()->format('Uu'),
            ),
        ],
    ]
);

dataset(
    'all possible transaction types',
    [
        'reuqest transaction' => [
            fn (
                ?CarbonInterface $startAt    = null,
                ?CarbonInterface $finishedAt = null,
                ?AbstractTrace $traceEvent   = null
            ) => new RequestTransaction(
                $traceEvent ?? new StartTrace(false, 0.0),
                $startAt?->format('Uu'),
                $finishedAt?->format('Uu')
            ),
        ],
        'octane reuqest transaction' => [
            fn (
                ?CarbonInterface $startAt    = null,
                ?CarbonInterface $finishedAt = null,
                ?AbstractTrace $traceEvent   = null
            ) => new OctaneRequestTransaction(
                $traceEvent ?? new StartTrace(false, 0.0),
                $startAt?->format('Uu'),
                $finishedAt?->format('Uu')
            ),
        ],
        'command transaction' => [
            fn (
                ?CarbonInterface $startAt    = null,
                ?CarbonInterface $finishedAt = null,
                ?AbstractTrace $traceEvent   = null
            ) => new CommandTransaction(
                $traceEvent ?? new StartTrace(false, 0.0),
                $startAt?->format('Uu'),
                $finishedAt?->format('Uu')
            ),
        ],
        'job transaction' => [
            fn (
                ?CarbonInterface $startAt    = null,
                ?CarbonInterface $finishedAt = null,
                ?AbstractTrace $traceEvent   = null
            ) => new JobTransaction(
                $traceEvent ?? new StartTrace(false, 0.0),
                $startAt?->format('Uu'),
                $finishedAt?->format('Uu')
            ),
        ],
    ]
);

dataset(
    'all possible span types',
    [
        'system span' => [
            fn (AbstractChildTraceEvent $transaction) => new SystemSpan(
                'test',
                'test',
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                'SubType',
                ($transaction->finishAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'plain span' => [
            fn (AbstractChildTraceEvent $transaction) => new PlainSpan(
                'test',
                'test',
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                'SubType',
                ($transaction->finishAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'query span' => [
            fn (AbstractChildTraceEvent $transaction) => new QuerySpan(
                'SELECT',
                ['exampleTable'],
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->finishAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'redis span' => [
            fn (AbstractChildTraceEvent $transaction) => new RedisCommandSpan(
                'command',
                'statement',
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->finishAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'http span' => [
            fn (AbstractChildTraceEvent $transaction) => new HttpSpan(
                'GET',
                new Uri('/'),
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->finishAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'render span' => [
            fn (AbstractChildTraceEvent $transaction) => new RenderSpan(
                'test',
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->finishAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'job queueing span' => [
            fn (AbstractChildTraceEvent $transaction) => new JobQueueingSpan(
                'test',
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->finishAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
    ]
);

dataset(
    'all non system span types',
    [
        'plain span' => [
            fn (AbstractChildTraceEvent $transaction) => new PlainSpan(
                'test',
                'test',
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'query span' => [
            fn (AbstractChildTraceEvent $transaction) => new QuerySpan(
                'SELECT',
                ['exampleTable'],
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'redis span' => [
            fn (AbstractChildTraceEvent $transaction) => new RedisCommandSpan(
                'command',
                'statement',
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'http span' => [
            fn (AbstractChildTraceEvent $transaction) => new HttpSpan(
                'GET',
                new Uri('/'),
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'render span' => [
            fn (AbstractChildTraceEvent $transaction) => new RenderSpan(
                'test',
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
        'job queueing span' => [
            fn (AbstractChildTraceEvent $transaction) => new JobQueueingSpan(
                'test',
                $transaction,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 1000000,
                ($transaction->startAt ?? Carbon::now()->format('Uu')) + 2000000
            ),
        ],
    ]
);

dataset(
    'possible values for completed detection',
    [
        'both times null' => [null, null, false],
        'only start time' => [now(), null, false],
        'only end time'   => [null, Carbon::now(), false],
        'both times'      => [now(), Carbon::now(), true],
    ]
);
