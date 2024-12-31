<?php

namespace Tests\Component\Elastic\Builder;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Nivseb\LaraMonitor\Contracts\ApmServiceContract;
use Nivseb\LaraMonitor\Elastic\Builder\MetaBuilder;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\User;
use Mockery;

test(
    'add metadata wrapper to result',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($buildTransaction());

        expect($result)
            ->toBeArray()
            ->toHaveCount(1)
            ->and(Arr::first($result))
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('metadata');
    }
)
    ->with('all possible transaction types');

test(
    'metadata has service data structure',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($buildTransaction());
        $metaData    = $result[0]['metadata'];

        expect($metaData)
            ->toBeArray()
            ->toHaveKey('service')
            ->and($metaData['service'])
            ->toBeArray()
            ->toHaveCount(9)
            ->toHaveKeys(['id', 'name', 'version', 'environment', 'node', 'agent', 'language', 'framework', 'runtime'])
            ->and($metaData['service']['node'])
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('configured_name')
            ->and($metaData['service']['agent'])
            ->toBeArray()
            ->toHaveCount(3)
            ->toHaveKeys(['ephemeral_id', 'name', 'version'])
            ->and($metaData['service']['language'])
            ->toBeArray()
            ->toHaveCount(2)
            ->toHaveKeys(['name', 'version'])
            ->and($metaData['service']['framework'])
            ->toBeArray()
            ->toHaveCount(2)
            ->toHaveKeys(['name', 'version']);
    }
)
    ->with('all possible transaction types');

test(
    'service metadata has correct values',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $expectedVersion            = fake()->semver();
        $expectedAgentName          = fake()->word();
        $expectedServiceId          = fake()->uuid();
        $expectedServiceName        = fake()->word();
        $expectedServiceVersion     = fake()->semver();
        $expectedServiceEnvironment = fake()->word();
        $expectedNodeName           = fake()->word();

        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->once()->withNoArgs()->andReturn($expectedVersion);
        $serviceMock->allows('getAgentName')->once()->withNoArgs()->andReturn($expectedAgentName);

        Config::set('lara-monitor.service.id', $expectedServiceId);
        Config::set('lara-monitor.service.name', $expectedServiceName);
        Config::set('lara-monitor.service.version', $expectedServiceVersion);
        Config::set('lara-monitor.service.env', $expectedServiceEnvironment);
        Config::set('lara-monitor.instance.name', $expectedNodeName);

        $metaBuilder     = new MetaBuilder();
        $result          = $metaBuilder->buildMetaRecords($buildTransaction());
        $serviceMetaData = $result[0]['metadata']['service'];

        expect($serviceMetaData)
            ->toBe(
                [
                    'id'          => $expectedServiceId,
                    'name'        => $expectedServiceName,
                    'version'     => $expectedServiceVersion,
                    'environment' => $expectedServiceEnvironment,
                    'node'        => [
                        'configured_name' => $expectedNodeName,
                    ],
                    'agent' => [
                        'ephemeral_id' => md5($expectedServiceId.':'.posix_getpid()),
                        'version'      => $expectedVersion,
                        'name'         => $expectedAgentName,
                    ],
                    'language' => [
                        'name'    => 'php',
                        'version' => \PHP_VERSION,
                    ],
                    'framework' => [
                        'name'    => 'laravel/framework',
                        'version' => App::version(),
                    ],
                    'runtime' => null,
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'process metadata mapped correct',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        global $argv;
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($buildTransaction());
        $metaData    = $result[0]['metadata'];

        expect($metaData)
            ->toBeArray()
            ->toHaveKey('process')
            ->and($metaData['process'])
            ->toBeArray()
            ->toHaveCount(3)
            ->toHaveKeys(['argv', 'pid', 'ppid'])
            ->toBe(
                [
                    'argv' => $argv,
                    'pid'  => posix_getpid(),
                    'ppid' => posix_getppid(),
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'system metadata mapped correct as container',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $expectedContainerId = fake()->uuid();
        $expectedHostName    = fake()->word();
        $expectedKubernetes  = [
            'namespace' => fake()->word(),
            'node'      => ['name' => fake()->word()],
            'pod'       => ['name' => fake()->word(), 'uid' => fake()->uuid()],
        ];

        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();

        Config::set('lara-monitor.instance.containerId', $expectedContainerId);
        Config::set('lara-monitor.instance.hostname', $expectedHostName);
        Config::set('lara-monitor.elasticApm.meta.kubernetes', $expectedKubernetes);

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($buildTransaction());
        $metaData    = $result[0]['metadata'];

        expect($metaData)
            ->toBeArray()
            ->toHaveKey('system')
            ->and($metaData['system'])
            ->toBeArray()
            ->toHaveCount(6)
            ->toHaveKeys(['architecture', 'configured_hostname', 'container', 'detected_hostname', 'kubernetes', 'platform'])
            ->toBe(
                [
                    'architecture'        => php_uname('m'),
                    'configured_hostname' => $expectedHostName,
                    'container'           => ['id' => $expectedContainerId],
                    'detected_hostname'   => null,
                    'kubernetes'          => $expectedKubernetes,
                    'platform'            => php_uname('s'),
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'system metadata mapped correct as non container',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $expectedHostName   = fake()->word();
        $expectedKubernetes = [
            'namespace' => fake()->word(),
            'node'      => ['name' => fake()->word()],
            'pod'       => ['name' => fake()->word(), 'uid' => fake()->uuid()],
        ];

        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();

        Config::set('lara-monitor.instance.containerId');
        Config::set('lara-monitor.instance.hostname', $expectedHostName);
        Config::set('lara-monitor.elasticApm.meta.kubernetes', $expectedKubernetes);

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($buildTransaction());
        $metaData    = $result[0]['metadata'];

        expect($metaData)
            ->toBeArray()
            ->toHaveKey('system')
            ->and($metaData['system'])
            ->toBeArray()
            ->toHaveCount(6)
            ->toHaveKeys(['architecture', 'configured_hostname', 'container', 'detected_hostname', 'kubernetes', 'platform'])
            ->toBe(
                [
                    'architecture'        => php_uname('m'),
                    'configured_hostname' => $expectedHostName,
                    'container'           => ['id' => null],
                    'detected_hostname'   => php_uname('n'),
                    'kubernetes'          => $expectedKubernetes,
                    'platform'            => php_uname('s'),
                ]
            );
    }
)
    ->with('all possible transaction types');

test(
    'map cloud meta data from config',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $expectedCloudData = [
            'account'           => ['id' => fake()->uuid(), 'name' => fake()->word()],
            'availability_zone' => fake()->word(),
            'instance'          => ['id' => fake()->uuid(), 'name' => fake()->word()],
            'machine'           => ['type' => fake()->word()],
            'project'           => ['id' => fake()->uuid(), 'name' => fake()->word()],
            'provider'          => fake()->word(),
            'region'            => fake()->word(),
            'service'           => ['name' => fake()->word()],
        ];

        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();

        Config::set('lara-monitor.elasticApm.meta.cloud', $expectedCloudData);

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($buildTransaction());
        $metaData    = $result[0]['metadata'];

        expect($metaData)
            ->toBeArray()
            ->toHaveKey('cloud')
            ->and($metaData['cloud'])
            ->toBe($expectedCloudData);
    }
)
    ->with('all possible transaction types');

test(
    'map metwork meta data from config',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $expectedNetworkData = ['connection' => ['type' => fake()->word()]];

        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();

        Config::set('lara-monitor.elasticApm.meta.network', $expectedNetworkData);

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($buildTransaction());
        $metaData    = $result[0]['metadata'];

        expect($metaData)
            ->toBeArray()
            ->toHaveKey('network')
            ->and($metaData['network'])
            ->toBe($expectedNetworkData);
    }
)
    ->with('all possible transaction types');

test(
    'map labels meta data from config',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $expectedLabelData = [
            'myLabelString'  => fake()->word(),
            'myLabelInteger' => fake()->numberBetween(),
            'myLabelFloat'   => fake()->randomFloat(),
            'myLabelBoolean' => fake()->boolean(),
        ];

        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();

        Config::set('lara-monitor.elasticApm.meta.labels', $expectedLabelData);

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($buildTransaction());
        $metaData    = $result[0]['metadata'];

        expect($metaData)
            ->toBeArray()
            ->toHaveKey('labels')
            ->and($metaData['labels'])
            ->toBe($expectedLabelData);
    }
)
    ->with('all possible transaction types');

test(
    'dont add user data without user',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();
        $transaction = $buildTransaction();
        $transaction->setUser(null);

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($transaction);
        $metaData    = $result[0]['metadata'];

        expect($metaData)
            ->toBeArray()
            ->toHaveKey('user')
            ->and($metaData['user'])
            ->toBeNull();
    }
)
    ->with('all possible transaction types');

test(
    'add user data for user at Transaction',
    /**
     * @param Closure(null|CarbonInterface, null|CarbonInterface, null|AbstractTrace) : AbstractTransaction $buildTransaction
     */
    function (Closure $buildTransaction): void {
        $user = new User(
            fake()->word(),
            fake()->uuid(),
            fake()->userName(),
            fake()->email(),
        );

        $serviceMock = Mockery::mock(ApmServiceContract::class);
        App::bind(ApmServiceContract::class, fn () => $serviceMock);
        $serviceMock->allows('getVersion')->andReturnNull();
        $serviceMock->allows('getAgentName')->andReturnNull();
        $transaction = $buildTransaction();
        $transaction->setUser($user);

        $metaBuilder = new MetaBuilder();
        $result      = $metaBuilder->buildMetaRecords($transaction);
        $metaData    = $result[0]['metadata'];

        expect($metaData)
            ->toBeArray()
            ->toHaveKey('user')
            ->and($metaData['user'])
            ->toBe(
                [
                    'domain'   => $user->domain,
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                ]
            );
    }
)
    ->with('all possible transaction types');
