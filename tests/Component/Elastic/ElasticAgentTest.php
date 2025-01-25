<?php

namespace Tests\Component\Elastic;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Nivseb\LaraMonitor\Contracts\ApmServiceContract;
use Nivseb\LaraMonitor\Contracts\Elastic\ErrorBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\MetaBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\MetricBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\SpanBuilderContract;
use Nivseb\LaraMonitor\Contracts\Elastic\TransactionBuilderContract;
use Nivseb\LaraMonitor\Elastic\ElasticAgent;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

test(
    'dont send data to apm server if no span records build',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();
        $spans       = new Collection();

        /** @var ApmServiceContract&MockInterface $serviceMock */
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);

        /** @var MockInterface&TransactionBuilderContract $transactionBuilderMock */
        $transactionBuilderMock = Mockery::mock(TransactionBuilderContract::class);

        /** @var MockInterface&SpanBuilderContract $spanBuilderMock */
        $spanBuilderMock = Mockery::mock(SpanBuilderContract::class);

        /** @var ErrorBuilderContract&MockInterface $errorBuilderMock */
        $errorBuilderMock = Mockery::mock(ErrorBuilderContract::class);

        /** @var MetaBuilderContract&MockInterface $metaBuilderMock */
        $metaBuilderMock = Mockery::mock(MetaBuilderContract::class);

        /** @var MetricBuilderContract&MockInterface $metricBuilderMock */
        $metricBuilderMock = Mockery::mock(MetricBuilderContract::class);

        $spanBuilderMock
            ->allows('buildSpanRecords')
            ->once()
            ->withArgs([$transaction, $spans])
            ->andReturn([]);

        Http::fake()->assertNothingSent();

        $elasticAgent = new ElasticAgent(
            $transactionBuilderMock,
            $spanBuilderMock,
            $errorBuilderMock,
            $metaBuilderMock,
            $metricBuilderMock
        );
        expect($elasticAgent->sendData($transaction, $spans))->toBeFalse();
    }
)
    ->with('all possible transaction types');

test(
    'dont send data to apm server if no transaction record build',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();
        $spans       = new Collection();
        $spanRecords = [['span' => ['id' => fake()->uuid()]]];

        /** @var ApmServiceContract&MockInterface $serviceMock */
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);

        /** @var MockInterface&TransactionBuilderContract $transactionBuilderMock */
        $transactionBuilderMock = Mockery::mock(TransactionBuilderContract::class);

        /** @var MockInterface&SpanBuilderContract $spanBuilderMock */
        $spanBuilderMock = Mockery::mock(SpanBuilderContract::class);

        /** @var ErrorBuilderContract&MockInterface $errorBuilderMock */
        $errorBuilderMock = Mockery::mock(ErrorBuilderContract::class);

        /** @var MetaBuilderContract&MockInterface $metaBuilderMock */
        $metaBuilderMock = Mockery::mock(MetaBuilderContract::class);

        /** @var MetricBuilderContract&MockInterface $metricBuilderMock */
        $metricBuilderMock = Mockery::mock(MetricBuilderContract::class);

        $spanBuilderMock
            ->allows('buildSpanRecords')
            ->once()
            ->withArgs([$transaction, $spans])
            ->andReturn($spanRecords);

        $transactionBuilderMock
            ->allows('buildTransactionRecords')
            ->once()
            ->withArgs([$transaction, $spans, $spanRecords])
            ->andReturn([]);

        Http::fake()->assertNothingSent();

        $elasticAgent = new ElasticAgent(
            $transactionBuilderMock,
            $spanBuilderMock,
            $errorBuilderMock,
            $metaBuilderMock,
            $metricBuilderMock
        );
        expect($elasticAgent->sendData($transaction, $spans))->toBeFalse();
    }
)
    ->with('all possible transaction types');

test(
    'send data correct formated to apm server',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction     = $buildTransaction();
        $metaDataJson    = '{"metadata":{"service":{"id":"7fe34ce204fd618264e39e6c97f16dd8","name":"larvel10","version":"dev-1.2.3","environment":"local","node":{"configured_name":"47a2ba1efc7f4ce42efd80119002cc85"},"agent":{"ephemeral_id":"675ac450ad80ea45d072ef51ae4bfefa","version":"dev-main","name":"lara-monitor"},"language":{"name":"php","version":"8.2.19"},"framework":{"name":"laravel\/framework","version":"10.48.23"},"runtime":null},"cloud":null,"labels":null,"network":null,"process":{"argv":[],"pid":133,"ppid":120},"system":{"architecture":"x86_64","configured_hostname":null,"container":{"id":"e415e11a5bc2c85d213c8f031a72cb177c170472c4cd7d6fccc4e5135e6a8e44"},"detected_hostname":null,"kubernetes":null,"platform":"Linux"},"user":null}}';
        $spanJson1       = '{"span":{"id":"5dd48d2911218297","name":"booting","parent_id":"5755cc09c5601510","trace_id":"d038ee40ea9d3cdfb78c5f312a423875","timestamp":1731804074080699,"duration":23.436,"start":0,"type":"boot","subtype":null,"action":null,"sync":true,"outcome":null,"sample_rate":1}}';
        $spanJson2       = '{"span":{"id":"628718d83e579604","name":"run","parent_id":"5755cc09c5601510","trace_id":"d038ee40ea9d3cdfb78c5f312a423875","timestamp":1731804074108999,"duration":66.408,"start":28,"type":"app","subtype":"handler","action":null,"sync":true,"outcome":null,"sample_rate":1}}';
        $errorJson       = '{"error":{"id":"574ecd26eff10f21","transaction_id":"5755cc09c5601510","parent_id":"628718d83e579604","trace_id":"d038ee40ea9d3cdfb78c5f312a423875","timestamp":1731804074117046,"culprit":"\/demo\/app\/routes\/web.php:23","exception":{"message":"Class \"LaraMonitorTransaction\" not found","type":"Class \"LaraMonitorTransaction\" not found","code":0,"handled":false,"stacktrace":[]}}}';
        $spanJson3       = '{"span":{"id":"48e133bf1d748e69","name":"render response","parent_id":"628718d83e579604","trace_id":"d038ee40ea9d3cdfb78c5f312a423875","timestamp":1731804074174755,"duration":0.237,"start":94,"type":"template","subtype":"response","action":"render","sync":true,"outcome":null,"sample_rate":1}}';
        $spanJson4       = '{"span":{"id":"1dfd6e4ddc739506","name":"terminating","parent_id":"5755cc09c5601510","trace_id":"d038ee40ea9d3cdfb78c5f312a423875","timestamp":1731804074175407,"duration":1.279,"start":94,"type":"terminate","subtype":null,"action":null,"sync":true,"outcome":null,"sample_rate":1}}';
        $metricJson1     = '{"metricset":{"samples":{"transaction.breakdown.count":{"value":1},"transaction.duration.sum.us":{"value":1},"transaction.self_time.sum.us":{"value":1},"span.self_time.count":{"value":1},"span.self_time.sum.us":{"value":0}},"timestamp":1731804074080699,"transaction":{"type":"request","name":"HEAD \/"},"span":{"type":"template","subtype":"response"}}}';
        $metricJson2     = '{"metricset":{"samples":{"transaction.breakdown.count":{"value":1},"transaction.duration.sum.us":{"value":1},"transaction.self_time.sum.us":{"value":1},"span.self_time.count":{"value":1},"span.self_time.sum.us":{"value":95}},"timestamp":1731804074080699,"transaction":{"type":"request","name":"HEAD \/"},"span":{"type":"app","subtype":"internal"}}}';
        $transactionJson = '{"transaction":{"id":"5755cc09c5601510","type":"request","trace_id":"d038ee40ea9d3cdfb78c5f312a423875","parent_id":null,"name":"HEAD \/","timestamp":1731804074080699,"duration":95.987,"sample_rate":1,"sampled":true,"span_count":{"started":4,"dropped":0},"dropped_spans_stats":null,"context":null,"outcome":"failure","session":null,"result":"HTTP 500"}}';

        Config::set('lara-monitor.elasticApm.baseUrl', 'https://test.localhost/');

        /** @var ApmServiceContract&MockInterface $serviceMock */
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);

        /** @var MockInterface&TransactionBuilderContract $transactionBuilderMock */
        $transactionBuilderMock = Mockery::mock(TransactionBuilderContract::class);

        /** @var MockInterface&SpanBuilderContract $spanBuilderMock */
        $spanBuilderMock = Mockery::mock(SpanBuilderContract::class);

        /** @var ErrorBuilderContract&MockInterface $errorBuilderMock */
        $errorBuilderMock = Mockery::mock(ErrorBuilderContract::class);

        /** @var MetaBuilderContract&MockInterface $metaBuilderMock */
        $metaBuilderMock = Mockery::mock(MetaBuilderContract::class);

        /** @var MetricBuilderContract&MockInterface $metricBuilderMock */
        $metricBuilderMock = Mockery::mock(MetricBuilderContract::class);

        $serviceMock->allows('getAgentName')->once()->withNoArgs()->andReturn(fake()->word());
        $serviceMock->allows('getVersion')->once()->withNoArgs()->andReturn(fake()->semver());

        $transactionBuilderMock
            ->allows('buildTransactionRecords')
            ->andReturn([json_decode($transactionJson, true)]);

        $spanBuilderMock
            ->allows('buildSpanRecords')
            ->andReturn(
                [
                    json_decode($spanJson1, true),
                    json_decode($spanJson2, true),
                    json_decode($spanJson3, true),
                    json_decode($spanJson4, true),
                ]
            );

        $errorBuilderMock
            ->allows('buildErrorRecords')
            ->andReturn([json_decode($errorJson, true)]);

        $metaBuilderMock
            ->allows('buildMetaRecords')
            ->andReturn([json_decode($metaDataJson, true)]);

        $metricBuilderMock
            ->allows('buildSpanMetrics')
            ->andReturn([json_decode($metricJson1, true), json_decode($metricJson2, true)]);

        $expectedJson = $metaDataJson."\n"
            .$transactionJson."\n"
            .$spanJson1."\n"
            .$spanJson2."\n"
            .$spanJson3."\n"
            .$spanJson4."\n"
            .$errorJson."\n"
            .$metricJson1."\n"
            .$metricJson2."\n";

        Http::fake(
            function (Request $request) use ($expectedJson) {
                expect($request->body())->toBe($expectedJson);

                return Http::response(status: 202);
            }
        )->assertNothingSent();

        $elasticAgent = new ElasticAgent(
            $transactionBuilderMock,
            $spanBuilderMock,
            $errorBuilderMock,
            $metaBuilderMock,
            $metricBuilderMock
        );
        expect($elasticAgent->sendData($transaction, new Collection()))->toBeTrue();
    }
)
    ->with('all possible transaction types');

test(
    'send correct user agent to apm server',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction    = $buildTransaction();
        $agentName      = fake()->word();
        $agentVersion   = fake()->semver();
        $serviceName    = fake()->word();
        $serviceVersion = fake()->semver();

        Config::set('lara-monitor.elasticApm.baseUrl', 'https://test.localhost/');
        Config::set('lara-monitor.service.name', $serviceName);
        Config::set('lara-monitor.service.version', $serviceVersion);

        /** @var ApmServiceContract&MockInterface $serviceMock */
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);

        /** @var MockInterface&TransactionBuilderContract $transactionBuilderMock */
        $transactionBuilderMock = Mockery::mock(TransactionBuilderContract::class);

        /** @var MockInterface&SpanBuilderContract $spanBuilderMock */
        $spanBuilderMock = Mockery::mock(SpanBuilderContract::class);

        /** @var ErrorBuilderContract&MockInterface $errorBuilderMock */
        $errorBuilderMock = Mockery::mock(ErrorBuilderContract::class);

        /** @var MetaBuilderContract&MockInterface $metaBuilderMock */
        $metaBuilderMock = Mockery::mock(MetaBuilderContract::class);

        /** @var MetricBuilderContract&MockInterface $metricBuilderMock */
        $metricBuilderMock = Mockery::mock(MetricBuilderContract::class);

        $serviceMock->allows('getAgentName')->once()->withNoArgs()->andReturn($agentName);
        $serviceMock->allows('getVersion')->once()->withNoArgs()->andReturn($agentVersion);

        $transactionBuilderMock
            ->allows('buildTransactionRecords')
            ->andReturn([['transaction' => ['id' => fake()->uuid()]]]);

        $spanBuilderMock
            ->allows('buildSpanRecords')
            ->andReturn([[['span' => ['id' => fake()->uuid()]]]]);

        $errorBuilderMock
            ->allows('buildErrorRecords')
            ->andReturn([]);

        $metaBuilderMock
            ->allows('buildMetaRecords')
            ->andReturn([]);

        $metricBuilderMock
            ->allows('buildSpanMetrics')
            ->andReturn([]);

        $expectedHeader = $agentName.' '.$agentVersion.' / '.$serviceName.' '.$serviceVersion;

        Http::fake(
            function (Request $request) use ($expectedHeader) {
                expect($request->header('User-Agent'))->toBe([$expectedHeader]);

                return Http::response(status: 202);
            }
        )->assertNothingSent();

        $elasticAgent = new ElasticAgent(
            $transactionBuilderMock,
            $spanBuilderMock,
            $errorBuilderMock,
            $metaBuilderMock,
            $metricBuilderMock
        );
        expect($elasticAgent->sendData($transaction, new Collection()))->toBeTrue();
    }
)
    ->with('all possible transaction types');

test(
    'send except header to apm server',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();

        Config::set('lara-monitor.elasticApm.baseUrl', 'https://test.localhost/');

        /** @var ApmServiceContract&MockInterface $serviceMock */
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);

        /** @var MockInterface&TransactionBuilderContract $transactionBuilderMock */
        $transactionBuilderMock = Mockery::mock(TransactionBuilderContract::class);

        /** @var MockInterface&SpanBuilderContract $spanBuilderMock */
        $spanBuilderMock = Mockery::mock(SpanBuilderContract::class);

        /** @var ErrorBuilderContract&MockInterface $errorBuilderMock */
        $errorBuilderMock = Mockery::mock(ErrorBuilderContract::class);

        /** @var MetaBuilderContract&MockInterface $metaBuilderMock */
        $metaBuilderMock = Mockery::mock(MetaBuilderContract::class);

        /** @var MetricBuilderContract&MockInterface $metricBuilderMock */
        $metricBuilderMock = Mockery::mock(MetricBuilderContract::class);

        $serviceMock->allows('getAgentName')->once()->withNoArgs()->andReturn(fake()->word());
        $serviceMock->allows('getVersion')->once()->withNoArgs()->andReturn(fake()->semver());

        $transactionBuilderMock
            ->allows('buildTransactionRecords')
            ->andReturn([['transaction' => ['id' => fake()->uuid()]]]);

        $spanBuilderMock
            ->allows('buildSpanRecords')
            ->andReturn([[['span' => ['id' => fake()->uuid()]]]]);

        $errorBuilderMock
            ->allows('buildErrorRecords')
            ->andReturn([]);

        $metaBuilderMock
            ->allows('buildMetaRecords')
            ->andReturn([]);

        $metricBuilderMock
            ->allows('buildSpanMetrics')
            ->andReturn([]);

        Http::fake(
            function (Request $request) {
                expect($request->header('Accept'))->toBe(['application/json']);

                return Http::response(status: 202);
            }
        )->assertNothingSent();

        $elasticAgent = new ElasticAgent(
            $transactionBuilderMock,
            $spanBuilderMock,
            $errorBuilderMock,
            $metaBuilderMock,
            $metricBuilderMock
        );
        expect($elasticAgent->sendData($transaction, new Collection()))->toBeTrue();
    }
)
    ->with('all possible transaction types');

test(
    'use correct url and intake v2 endpoint for apm server',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();

        Config::set('lara-monitor.elasticApm.baseUrl', 'https://fake.apm-servicer.localhost/test');

        /** @var ApmServiceContract&MockInterface $serviceMock */
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);

        /** @var MockInterface&TransactionBuilderContract $transactionBuilderMock */
        $transactionBuilderMock = Mockery::mock(TransactionBuilderContract::class);

        /** @var MockInterface&SpanBuilderContract $spanBuilderMock */
        $spanBuilderMock = Mockery::mock(SpanBuilderContract::class);

        /** @var ErrorBuilderContract&MockInterface $errorBuilderMock */
        $errorBuilderMock = Mockery::mock(ErrorBuilderContract::class);

        /** @var MetaBuilderContract&MockInterface $metaBuilderMock */
        $metaBuilderMock = Mockery::mock(MetaBuilderContract::class);

        /** @var MetricBuilderContract&MockInterface $metricBuilderMock */
        $metricBuilderMock = Mockery::mock(MetricBuilderContract::class);

        $serviceMock->allows('getAgentName')->once()->withNoArgs()->andReturn(fake()->word());
        $serviceMock->allows('getVersion')->once()->withNoArgs()->andReturn(fake()->semver());

        $transactionBuilderMock
            ->allows('buildTransactionRecords')
            ->andReturn([['transaction' => ['id' => fake()->uuid()]]]);

        $spanBuilderMock
            ->allows('buildSpanRecords')
            ->andReturn([[['span' => ['id' => fake()->uuid()]]]]);

        $errorBuilderMock
            ->allows('buildErrorRecords')
            ->andReturn([]);

        $metaBuilderMock
            ->allows('buildMetaRecords')
            ->andReturn([]);

        $metricBuilderMock
            ->allows('buildSpanMetrics')
            ->andReturn([]);

        Http::fake(
            function (Request $request) {
                expect($request->url())->toBe('https://fake.apm-servicer.localhost/test/intake/v2/events');

                return Http::response(status: 202);
            }
        )->assertNothingSent();

        $elasticAgent = new ElasticAgent(
            $transactionBuilderMock,
            $spanBuilderMock,
            $errorBuilderMock,
            $metaBuilderMock,
            $metricBuilderMock
        );
        expect($elasticAgent->sendData($transaction, new Collection()))->toBeTrue();
    }
)
    ->with('all possible transaction types');

test(
    'return false if the apm server response with other status code',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction, int $apmServerStatus): void {
        $transaction = $buildTransaction();

        Config::set('lara-monitor.elasticApm.baseUrl', 'https://test.localhost/');

        /** @var ApmServiceContract&MockInterface $serviceMock */
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);

        /** @var MockInterface&TransactionBuilderContract $transactionBuilderMock */
        $transactionBuilderMock = Mockery::mock(TransactionBuilderContract::class);

        /** @var MockInterface&SpanBuilderContract $spanBuilderMock */
        $spanBuilderMock = Mockery::mock(SpanBuilderContract::class);

        /** @var ErrorBuilderContract&MockInterface $errorBuilderMock */
        $errorBuilderMock = Mockery::mock(ErrorBuilderContract::class);

        /** @var MetaBuilderContract&MockInterface $metaBuilderMock */
        $metaBuilderMock = Mockery::mock(MetaBuilderContract::class);

        /** @var MetricBuilderContract&MockInterface $metricBuilderMock */
        $metricBuilderMock = Mockery::mock(MetricBuilderContract::class);

        $serviceMock->allows('getAgentName')->once()->withNoArgs()->andReturn(fake()->word());
        $serviceMock->allows('getVersion')->once()->withNoArgs()->andReturn(fake()->semver());

        $transactionBuilderMock
            ->allows('buildTransactionRecords')
            ->andReturn([['transaction' => ['id' => fake()->uuid()]]]);

        $spanBuilderMock
            ->allows('buildSpanRecords')
            ->andReturn([[['span' => ['id' => fake()->uuid()]]]]);

        $errorBuilderMock
            ->allows('buildErrorRecords')
            ->andReturn([]);

        $metaBuilderMock
            ->allows('buildMetaRecords')
            ->andReturn([]);

        $metricBuilderMock
            ->allows('buildSpanMetrics')
            ->andReturn([]);

        Http::fake(['https://test.localhost/intake/v2/events' => Http::response(status: $apmServerStatus)]);

        $elasticAgent = new ElasticAgent(
            $transactionBuilderMock,
            $spanBuilderMock,
            $errorBuilderMock,
            $metaBuilderMock,
            $metricBuilderMock
        );
        expect($elasticAgent->sendData($transaction, new Collection()))->toBeFalse();
    }
)
    ->with('all possible transaction types')
    ->with(
        [
            'successful'      => [200],
            'created'         => [201],
            'not found'       => [404],
            'timeout'         => [408],
            'unauthenticated' => [403],
            'server error'    => [500],
        ]
    );

test(
    'return false on connection fail',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $transaction = $buildTransaction();

        Config::set('lara-monitor.elasticApm.baseUrl', 'https://test.localhost/');

        /** @var ApmServiceContract&MockInterface $serviceMock */
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);

        /** @var MockInterface&TransactionBuilderContract $transactionBuilderMock */
        $transactionBuilderMock = Mockery::mock(TransactionBuilderContract::class);

        /** @var MockInterface&SpanBuilderContract $spanBuilderMock */
        $spanBuilderMock = Mockery::mock(SpanBuilderContract::class);

        /** @var ErrorBuilderContract&MockInterface $errorBuilderMock */
        $errorBuilderMock = Mockery::mock(ErrorBuilderContract::class);

        /** @var MetaBuilderContract&MockInterface $metaBuilderMock */
        $metaBuilderMock = Mockery::mock(MetaBuilderContract::class);

        /** @var MetricBuilderContract&MockInterface $metricBuilderMock */
        $metricBuilderMock = Mockery::mock(MetricBuilderContract::class);

        $serviceMock->allows('getAgentName')->once()->withNoArgs()->andReturn(fake()->word());
        $serviceMock->allows('getVersion')->once()->withNoArgs()->andReturn(fake()->semver());

        $transactionBuilderMock
            ->allows('buildTransactionRecords')
            ->andReturn([['transaction' => ['id' => fake()->uuid()]]]);

        $spanBuilderMock
            ->allows('buildSpanRecords')
            ->andReturn([[['span' => ['id' => fake()->uuid()]]]]);

        $errorBuilderMock
            ->allows('buildErrorRecords')
            ->andReturn([]);

        $metaBuilderMock
            ->allows('buildMetaRecords')
            ->andReturn([]);

        $metricBuilderMock
            ->allows('buildSpanMetrics')
            ->andReturn([]);

        Http::fake(['https://test.localhost/intake/v2/events' => fn () => throw new ConnectionException()]);

        $elasticAgent = new ElasticAgent(
            $transactionBuilderMock,
            $spanBuilderMock,
            $errorBuilderMock,
            $metaBuilderMock,
            $metricBuilderMock
        );
        expect($elasticAgent->sendData($transaction, new Collection()))->toBeFalse();
    }
)
    ->with('all possible transaction types');
