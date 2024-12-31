<?php

namespace Nivseb\LaraMonitor\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Nivseb\LaraMonitor\Contracts\AnalyserContract;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

/**
 * @method static void analyse(AbstractTransaction $transaction, Collection $spans, int|null $allowedExitCode)
 *
 * @see AnalyserContract
 */
class LaraMonitorAnalyser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AnalyserContract::class;
    }
}
