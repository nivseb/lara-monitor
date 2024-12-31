<?php

namespace Nivseb\LaraMonitor\Contracts\Collector;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\Error;
use Throwable;

interface ErrorCollectorContract
{
    public function captureExceptionAsError(
        Throwable $exception,
        bool $handled = false,
        ?CarbonInterface $time = null
    ): ?Error;

    public function captureError(
        string $type,
        int|string $code,
        string $message,
        bool $handled = false,
        ?CarbonInterface $time = null,
        ?Throwable $exception = null
    ): ?Error;
}
