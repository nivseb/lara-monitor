<?php

namespace Tests\Component\Services\Mapper;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Nivseb\LaraMonitor\Services\Mapper;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\RenderSpan;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;
use Mockery;
use stdClass;

test(
    'span is build as render span',
    function (AbstractChildTraceEvent $traceEvent): void {
        $mapper = new Mapper();
        $span   = $mapper->buildRenderSpanForResponse(
            $traceEvent,
            fake()->regexify('\w{10}'),
            Carbon::now()
        );

        expect($span)->toBeInstanceOf(RenderSpan::class);
    }
)
    ->with('all possible child trace events');

test(
    'span get correct trace parent',
    function (AbstractChildTraceEvent $traceEvent): void {
        $mapper = new Mapper();

        /** @var RenderSpan $span */
        $span = $mapper->buildRenderSpanForResponse(
            $traceEvent,
            fake()->regexify('\w{10}'),
            Carbon::now()
        );

        expect($span->parentEvent)->toBe($traceEvent);
    }
)
    ->with('all possible child trace events');

test(
    'span receive given date as start time',
    function (AbstractChildTraceEvent $traceEvent): void {
        $expectedDate = new Carbon(fake()->dateTime());

        $mapper = new Mapper();

        /** @var RenderSpan $span */
        $span = $mapper->buildRenderSpanForResponse(
            $traceEvent,
            fake()->regexify('\w{10}'),
            $expectedDate
        );

        expect($span->startAt)->toBe($expectedDate);
    }
)
    ->with('all possible child trace events');

test(
    'span get render type',
    function (callable $responseBuilder, string $expectedType): void {
        $mapper = new Mapper();

        /** @var RenderSpan $span */
        $span = $mapper->buildRenderSpanForResponse(
            new RequestTransaction(new StartTrace(false, 0.0)),
            $responseBuilder(),
            Carbon::now()
        );

        expect($span->type)->toBe($expectedType);
    }
)
    ->with(
        [
            'string'              => [fn () => fake()->word(), 'other'],
            'integer'             => [fn () => fake()->numberBetween(1), 'other'],
            'float'               => [fn () => fake()->randomFloat(2), 'other'],
            'boolean'             => [fn () => fake()->boolean(), 'other'],
            'array'               => [fn () => [fake()->boolean()], 'other'],
            'collection'          => [fn () => new Collection(), 'other'],
            'json string'         => [fn () => '{"test":123}', 'other'],
            'object'              => [fn () => new stdClass(), 'other'],
            'response'            => [fn () => Mockery::mock(Response::class), 'response'],
            'json response'       => [fn () => Mockery::mock(JsonResponse::class), 'json'],
            'resource'            => [fn () => Mockery::mock(JsonResource::class), 'resource'],
            'resource collection' => [fn () => Mockery::mock(ResourceCollection::class), 'resource'],
            'view'                => [fn () => Mockery::mock(View::class), 'view'],
        ]
    );
