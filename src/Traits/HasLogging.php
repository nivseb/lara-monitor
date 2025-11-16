<?php

namespace Nivseb\LaraMonitor\Traits;

use Illuminate\Support\Facades\Log;
use Throwable;

trait HasLogging
{
    protected function logForLaraMonitorFail(string $message, Throwable $exception): void
    {
        dump(['Lara-Monitor: '.$message, ['error' => $exception->getMessage()]]);
        Log::warning('Lara-Monitor: '.$message, ['error' => $exception->getMessage()]);
    }

    protected function logForLaraMonitor(string $message, array $context = []): void
    {
        dump(['Lara-Monitor: '.$message, $context]);
        Log::notice('Lara-Monitor: '.$message, $context);
    }
}
