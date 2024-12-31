<?php

namespace Nivseb\LaraMonitor\Facades;

use Illuminate\Support\Facades\Facade;
use Nivseb\LaraMonitor\Contracts\ApmServiceContract;

/**
 * @method static bool        finishCurrentTransaction()
 * @method static string|null getVersion()
 * @method static string|null getAgentName()
 * @method static void        allowErrorResponse(int $allowedExitCode)
 *
 * @see ApmServiceContract
 */
class LaraMonitorApm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ApmServiceContract::class;
    }
}
