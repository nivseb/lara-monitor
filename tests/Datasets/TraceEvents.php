<?php

namespace Tests\Datasets;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use GuzzleHttp\Psr7\Uri;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
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
        'reuqest transaction' => [fn () => new RequestTransaction(new StartTrace(false, 0.0)),
        ],
        'octane reuqest transaction' => [fn () => new OctaneRequestTransaction(new StartTrace(false, 0.0)),
        ],
        'command transaction' => [fn () => new CommandTransaction(new StartTrace(false, 0.0)),
        ],
        'job transaction' => [fn () => new JobTransaction(new StartTrace(false, 0.0)),
        ],
        'system span' => [
            fn () => new SystemSpan(
                'test',
                'test',
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()
            ),
        ],
        'plain span' => [
            fn () => new PlainSpan(
                'test',
                'test',
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()
            ),
        ],
        'query span' => [
            fn () => new QuerySpan(
                'SELECT',
                ['exampleTable'],
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now(),
                Carbon::now(),
            ),
        ],
        'redis span' => [
            fn () => new RedisCommandSpan(
                'command',
                'statement',
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now(),
                Carbon::now(),
            ),
        ],
        'http span' => [
            fn () => new HttpSpan(
                'GET',
                new Uri('/'),
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now()
            ),
        ],
        'render span' => [
            fn () => new RenderSpan(
                'test',
                new RequestTransaction(new StartTrace(false, 0.0)),
                Carbon::now(),
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
                $startAt,
                $finishedAt
            ),
        ],
        'octane reuqest transaction' => [
            fn (
                ?CarbonInterface $startAt    = null,
                ?CarbonInterface $finishedAt = null,
                ?AbstractTrace $traceEvent   = null
            ) => new OctaneRequestTransaction(
                $traceEvent ?? new StartTrace(false, 0.0),
                $startAt,
                $finishedAt
            ),
        ],
        'command transaction' => [
            fn (
                ?CarbonInterface $startAt    = null,
                ?CarbonInterface $finishedAt = null,
                ?AbstractTrace $traceEvent   = null
            ) => new CommandTransaction(
                $traceEvent ?? new StartTrace(false, 0.0),
                $startAt,
                $finishedAt
            ),
        ],
        'job transaction' => [
            fn (
                ?CarbonInterface $startAt    = null,
                ?CarbonInterface $finishedAt = null,
                ?AbstractTrace $traceEvent   = null
            ) => new JobTransaction(
                $traceEvent ?? new StartTrace(false, 0.0),
                $startAt,
                $finishedAt
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
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                'SubType',
                ($transaction->finishAt?->clone() ?? Carbon::now())->addSeconds(2)
            ),
        ],
        'plain span' => [
            fn (AbstractChildTraceEvent $transaction) => new PlainSpan(
                'test',
                'test',
                $transaction,
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                'SubType',
                ($transaction->finishAt?->clone() ?? Carbon::now())->addSeconds(2)
            ),
        ],
        'query span' => [
            fn (AbstractChildTraceEvent $transaction) => new QuerySpan(
                'SELECT',
                ['exampleTable'],
                $transaction,
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                ($transaction->finishAt?->clone() ?? Carbon::now())->addSeconds(2)
            ),
        ],
        'redis span' => [
            fn (AbstractChildTraceEvent $transaction) => new RedisCommandSpan(
                'command',
                'statement',
                $transaction,
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                ($transaction->finishAt?->clone() ?? Carbon::now())->addSeconds(2)
            ),
        ],
        'http span' => [
            fn (AbstractChildTraceEvent $transaction) => new HttpSpan(
                'GET',
                new Uri('/'),
                $transaction,
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                ($transaction->finishAt?->clone() ?? Carbon::now())->addSeconds(2)
            ),
        ],
        'render span' => [
            fn (AbstractChildTraceEvent $transaction) => new RenderSpan(
                'test',
                $transaction,
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                ($transaction->finishAt?->clone() ?? Carbon::now())->addSeconds(2)
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
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                ($transaction->startAt?->clone() ?? Carbon::now())->addSeconds(2)
            ),
        ],
        'query span' => [
            fn (AbstractChildTraceEvent $transaction) => new QuerySpan(
                'SELECT',
                ['exampleTable'],
                $transaction,
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                ($transaction->startAt?->clone() ?? Carbon::now())->addSeconds(2)
            ),
        ],
        'redis span' => [
            fn (AbstractChildTraceEvent $transaction) => new RedisCommandSpan(
                'command',
                'statement',
                $transaction,
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                ($transaction->startAt?->clone() ?? Carbon::now())->addSeconds(2)
            ),
        ],
        'http span' => [
            fn (AbstractChildTraceEvent $transaction) => new HttpSpan(
                'GET',
                new Uri('/'),
                $transaction,
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                ($transaction->startAt?->clone() ?? Carbon::now())->addSeconds(2)
            ),
        ],
        'render span' => [
            fn (AbstractChildTraceEvent $transaction) => new RenderSpan(
                'test',
                $transaction,
                ($transaction->startAt?->clone() ?? Carbon::now())->addSecond(),
                ($transaction->startAt?->clone() ?? Carbon::now())->addSeconds(2)
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
