<?php

namespace Nivseb\LaraMonitor\Elastic;

use Illuminate\Support\Facades\Config;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Throwable;

class LogDataProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        if (!Config::get('lara-monitor.enabled')) {
            return $record;
        }

        $record->extra['service'] = [
            'name'        => Config::get('lara-monitor.service.name'),
            'version'     => Config::get('lara-monitor.service.version'),
            'environment' => Config::get('lara-monitor.service.env'),
        ];
        $containerId = Config::get('lara-monitor.instance.containerId');
        if ($containerId) {
            $record->extra['container'] = ['id' => $containerId];
        }

        try {
            $transaction       = LaraMonitorStore::getTransaction();
            $currentTraceEvent = LaraMonitorStore::getCurrentTraceEvent();
        } catch (Throwable) {
            return $record;
        }
        if ($transaction) {
            $record->extra['trace']       = ['id' => $transaction->getTraceId()];
            $record->extra['transaction'] = ['id' => $transaction->getId()];
        }
        if ($currentTraceEvent instanceof AbstractSpan) {
            $record->extra['span'] = ['id' => $currentTraceEvent->getId()];
        }

        return $record;
    }
}
