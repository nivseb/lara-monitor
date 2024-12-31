<?php

namespace Nivseb\LaraMonitor\Collectors;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Error;
use Throwable;

class ErrorCollector
{
    public function captureExceptionAsError(
        Throwable $exception,
        bool $handled = false,
        ?CarbonInterface $time = null
    ): ?Error {
        return $this->captureError(
            Str::afterLast($exception::class, '\\'),
            $exception->getCode(),
            $exception->getMessage(),
            $handled,
            $time,
            $exception
        );
    }

    public function captureError(
        string $type,
        int|string $code,
        string $message,
        bool $handled = false,
        ?CarbonInterface $time = null,
        ?Throwable $exception = null
    ): ?Error {
        $currentEvent = LaraMonitorStore::getCurrentTraceEvent();
        if (!$currentEvent) {
            return null;
        }

        return new Error(
            $currentEvent,
            $type,
            $code,
            $message,
            $handled,
            $time?->clone() ?? Carbon::now(),
            $exception
        );
    }
}
