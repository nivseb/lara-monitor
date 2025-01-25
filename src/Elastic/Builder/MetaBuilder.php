<?php

namespace Nivseb\LaraMonitor\Elastic\Builder;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Nivseb\LaraMonitor\Contracts\Elastic\MetaBuilderContract;
use Nivseb\LaraMonitor\Facades\LaraMonitorApm;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\User;

class MetaBuilder implements MetaBuilderContract
{
    public function buildMetaRecords(AbstractTransaction $transaction): array
    {
        return array_filter([['metadata' => $this->buildMetaDataRecord($transaction)]]);
    }

    protected function buildMetaDataRecord(AbstractTransaction $transaction): array
    {
        return [
            'service' => $this->buildServiceData(),
            'cloud'   => Config::get('lara-monitor.elasticApm.meta.cloud'),
            'labels'  => Config::get('lara-monitor.elasticApm.meta.labels'),
            'network' => Config::get('lara-monitor.elasticApm.meta.network'),
            'process' => $this->buildProcessData(),
            'system'  => $this->buildSystemData(),
            'user'    => $this->buildUserData($transaction->getUser()),
        ];
    }

    protected function buildProcessData(): ?array
    {
        return [
            'argv' => Arr::get($_SERVER, 'argv'),
            'pid'  => posix_getpid(),
            'ppid' => posix_getppid(),
        ];
    }

    protected function buildSystemData(): array
    {
        $containerId = Config::get('lara-monitor.instance.containerId');

        return [
            'architecture'        => php_uname('m'),
            'configured_hostname' => Config::get('lara-monitor.instance.hostname'),
            'container'           => ['id' => $containerId],
            'detected_hostname'   => $containerId ? null : php_uname('n'),
            'kubernetes'          => Config::get('lara-monitor.elasticApm.meta.kubernetes'),
            'platform'            => php_uname('s'),
        ];
    }

    protected function buildUserData(?User $userData): ?array
    {
        if (!$userData) {
            return null;
        }

        return [
            'domain'   => $userData->domain,
            'id'       => $userData->id,
            'username' => $userData->username,
            'email'    => $userData->email,
        ];
    }

    protected function buildServiceData(): array
    {
        return [
            'id'          => Config::get('lara-monitor.service.id'),
            'name'        => Config::get('lara-monitor.service.name'),
            'version'     => Config::get('lara-monitor.service.version'),
            'environment' => Config::get('lara-monitor.service.env'),
            'node'        => [
                'configured_name' => Config::get('lara-monitor.instance.name'),
            ],
            'agent' => [
                'ephemeral_id' => md5(Config::get('lara-monitor.service.id').':'.posix_getpid()),
                'version'      => LaraMonitorApm::getVersion(),
                'name'         => LaraMonitorApm::getAgentName(),
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
        ];
    }
}
