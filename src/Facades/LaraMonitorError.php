<?php

namespace Nivseb\LaraMonitor\Facades;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Facade;
use Nivseb\LaraMonitor\Contracts\Collector\ErrorCollectorContract;
use Nivseb\LaraMonitor\Struct\Error;
use Throwable;

/**
 * @method static Error|null captureExceptionAsError(Throwable $exception, bool $handled = false, CarbonInterface $time = null)
 * @method static Error|null captureError(string $type, int|string $code, string $message, bool $handled = false, ?CarbonInterface $time = null, ?Throwable $exception = null)
 *
 * @see ErrorCollectorContract
 */
class LaraMonitorError extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ErrorCollectorContract::class;
    }
}
