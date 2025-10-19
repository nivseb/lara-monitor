<?php

namespace Nivseb\LaraMonitor\Traits;

use Illuminate\Support\Facades\Log;
use Throwable;

trait HasLogging
{
    protected function logForLaraMonitorFail(string $message, Throwable $exception): void
    {
        Log::warning('Lara-Monitor: '.$message, ['error' => $exception->getMessage()]);
    }

    protected function logForLaraMonitor(string $message, array $context = []): void
    {
        Log::notice('Lara-Monitor: '.$message, $context);
    }
}
