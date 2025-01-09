<?php

namespace Tests\Unit\Struct\Spans;

use Carbon\Carbon;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

test(
    'query span name is query type and tables',
    function (string $queryType, array $tables, string $expectedName): void {
        $span = new QuerySpan(
            $queryType,
            $tables,
            new RequestTransaction(new StartTrace(false, 0.0)),
            Carbon::now(),
            Carbon::now()
        );

        expect($span->getName())->toBe($expectedName);
    }
)
    ->with(
        [
            'single select query' => [
                'SELECT',
                ['exampleTable'],
                'SELECT exampleTable',
            ],
            'single insert query' => [
                'INSERT',
                ['exampleTable'],
                'INSERT exampleTable',
            ],
            'single update query' => [
                'UPDATE',
                ['exampleTable'],
                'UPDATE exampleTable',
            ],
            'single delete query' => [
                'DELETE',
                ['exampleTable'],
                'DELETE exampleTable',
            ],
            'multiple select query' => [
                'SELECT',
                ['exampleTable1', 'exampleTable2', 'exampleTable3'],
                'SELECT exampleTable1,exampleTable2,exampleTable3',
            ],
            'multiple insert query' => [
                'INSERT',
                ['exampleTable1', 'exampleTable2', 'exampleTable3'],
                'INSERT exampleTable1,exampleTable2,exampleTable3',
            ],
            'multiple update query' => [
                'UPDATE',
                ['exampleTable1', 'exampleTable2', 'exampleTable3'],
                'UPDATE exampleTable1,exampleTable2,exampleTable3',
            ],
            'multiple delete query' => [
                'DELETE',
                ['exampleTable1', 'exampleTable2', 'exampleTable3'],
                'DELETE exampleTable1,exampleTable2,exampleTable3',
            ],
            'multiple create query' => [
                'CREATE',
                ['exampleTable'],
                'CREATE exampleTable',
            ],
            'multiple alter query' => [
                'ALTER',
                ['exampleTable'],
                'ALTER exampleTable',
            ],
            'multiple drop query' => [
                'DROP',
                ['exampleTable'],
                'DROP exampleTable',
            ],
            'create query' => [
                'CREATE',
                ['exampleTable'],
                'CREATE exampleTable',
            ],
            'alter query' => [
                'ALTER',
                ['exampleTable'],
                'ALTER exampleTable',
            ],
            'drop query' => [
                'DROP',
                ['exampleTable'],
                'DROP exampleTable',
            ],
            'execute procedure' => [
                'EXEC',
                ['exampleProcedure'],
                'EXEC exampleProcedure',
            ],
        ]
    );

test(
    'generate trace event id that match W3C requirement',
    function (): void {
        $span = new QuerySpan(
            fake()->regexify('\w{10}'),
            [fake()->regexify('\w{10}')],
            new RequestTransaction(new StartTrace(false, 0.0)),
            Carbon::now(),
            Carbon::now()
        );
        expect($span->id)
            ->toMatch('/^[a-f0-9]{16}$/')
            ->toBe($span->getId());
    }
);

test(
    'getTrace return parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new QuerySpan(
            fake()->regexify('\w{10}'),
            [fake()->regexify('\w{10}')],
            $transaction,
            Carbon::now(),
            Carbon::now()
        );
        expect($span->getTrace())->toBe($parent);
    }
);

test(
    'getTraceId return trace id from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new QuerySpan(
            fake()->regexify('\w{10}'),
            [fake()->regexify('\w{10}')],
            $transaction,
            Carbon::now(),
            Carbon::now()
        );
        expect($span->getTraceId())->toBe($parent->getTraceId());
    }
);

test(
    'isSampled return sampled flag from parent event',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new QuerySpan(
            fake()->regexify('\w{10}'),
            [fake()->regexify('\w{10}')],
            $transaction,
            Carbon::now(),
            Carbon::now()
        );
        expect($span->isSampled())->toBe($parent->isSampled());
    }
);

test(
    'determined isCompleted flag with start and finish time',
    function (): void {
        $parent      = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new QuerySpan(
            fake()->regexify('\w{10}'),
            [fake()->regexify('\w{10}')],
            $transaction,
            Carbon::now(),
            Carbon::now()
        );
        expect($span->isCompleted())->toBeTrue();
    }
);

test(
    'generate w3c trace parent with correct feature flag for sampled span',
    function (): void {
        $parent = new StartTrace(true, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new QuerySpan(
            fake()->regexify('\w{10}'),
            [fake()->regexify('\w{10}')],
            $transaction,
            Carbon::now(),
            Carbon::now()
        );

        expect($span->asW3CTraceParent()->traceFlags)->toBe('01');
    }
);


test(
    'generate w3c trace parent with correct feature flag for unsampled span',
    function (): void {
        $parent = new StartTrace(false, 0.00);
        $transaction = new RequestTransaction($parent);
        $span        = new QuerySpan(
            fake()->regexify('\w{10}'),
            [fake()->regexify('\w{10}')],
            $transaction,
            Carbon::now(),
            Carbon::now()
        );

        expect($span->asW3CTraceParent()->traceFlags)->toBe('00');
    }
);
