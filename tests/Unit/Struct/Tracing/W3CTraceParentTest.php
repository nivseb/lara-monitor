<?php

namespace Tests\Unit\Struct\Tracing;

use Nivseb\LaraMonitor\Exceptions\InvalidTraceFormatException;
use Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent;
use InvalidArgumentException;

test(
    'build instance from string',
    /**
     * @throws InvalidTraceFormatException
     */
    function (): void {
        $instance = W3CTraceParent::createFromString('00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01');
        expect($instance)->toBeInstanceOf(W3CTraceParent::class)
            ->and($instance->version)->toBe('00')
            ->and($instance->traceId)->toBe('0af7651916cd43dd8448eb211c80319c')
            ->and($instance->parentId)->toBe('b7ad6b7169203331')
            ->and($instance->traceFlags)->toBe('01');
    }
);

test(
    'build instance via constructor',
    function (): void {
        $instance = new W3CTraceParent(
            '00',
            '0af7651916cd43dd8448eb211c80319c',
            'b7ad6b7169203331',
            '01'
        );
        expect($instance)->toBeInstanceOf(W3CTraceParent::class)
            ->and($instance->version)->toBe('00')
            ->and($instance->traceId)->toBe('0af7651916cd43dd8448eb211c80319c')
            ->and($instance->parentId)->toBe('b7ad6b7169203331')
            ->and($instance->traceFlags)->toBe('01');
    }
);

test(
    'unserialize to instance',
    function (): void {
        $instance = unserialize(
            'O:48:"Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent":1:{i:0;s:55:"00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01";}'
        );
        expect($instance)->toBeInstanceOf(W3CTraceParent::class)
            ->and($instance->version)->toBe('00')
            ->and($instance->traceId)->toBe('0af7651916cd43dd8448eb211c80319c')
            ->and($instance->parentId)->toBe('b7ad6b7169203331')
            ->and($instance->traceFlags)->toBe('01');
    }
);

test(
    'cast to string',
    function (): void {
        $instance = new W3CTraceParent(
            '00',
            '0af7651916cd43dd8448eb211c80319c',
            'b7ad6b7169203331',
            '01'
        );
        expect((string) $instance)->toBe('00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01');
    }
);

test(
    'cast to json',
    function (): void {
        $instance = new W3CTraceParent(
            '00',
            '0af7651916cd43dd8448eb211c80319c',
            'b7ad6b7169203331',
            '01'
        );
        expect(json_encode($instance))->toBe('"00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01"');
    }
);

test(
    'serialize instance',
    function (): void {
        $instance = new W3CTraceParent(
            '00',
            '0af7651916cd43dd8448eb211c80319c',
            'b7ad6b7169203331',
            '01'
        );
        expect(serialize($instance))
            ->toBe(
                'O:48:"Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent":1:{i:0;s:55:"00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01";}'
            );
    }
);

test(
    'detect `is sampled` for all possible flags',
    function (string $flag): void {
        $instance = new W3CTraceParent(
            '00',
            '0af7651916cd43dd8448eb211c80319c',
            'b7ad6b7169203331',
            $flag
        );
        expect($instance->sampled())->toBeTrue();
    }
)
    ->with('sampled flags');

test(
    'detect `is not sampled` for all possible flags',
    function (string $flag): void {
        $instance = new W3CTraceParent(
            '00',
            '0af7651916cd43dd8448eb211c80319c',
            'b7ad6b7169203331',
            $flag
        );
        expect($instance->sampled())->toBeFalse();
    }
)
    ->with('not sampled flags');

test(
    'check for invalid trace header by creating from string',
    /**
     * @throws InvalidTraceFormatException
     */
    function (string $header): void {
        W3CTraceParent::createFromString($header);
    }
)
    ->with('invalid trace header')
    ->throws(
        InvalidTraceFormatException::class,
        'Invalid trace format!'
    );

test(
    'check for invalid trace header by unserialize',
    function (string $header): void {
        unserialize('O:48:"Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent":1:{i:0;s:'.strlen($header).':"'.$header.'";}');
    }
)
    ->with('invalid trace header')
    ->throws(
        InvalidArgumentException::class,
        'Can`t unserialize string to Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent'
    );
