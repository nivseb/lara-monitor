<?php

namespace Nivseb\LaraMonitor\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

/**
 * @method static bool                         setTransaction(AbstractTransaction $transaction, Collection $spans)
 * @method static AbstractTransaction|null     getTransaction()
 * @method static AbstractChildTraceEvent|null getCurrentTraceEvent()
 * @method static Collection|null              getSpanList()
 * @method static array|null                   getDroppedSpanStats()
 * @method static bool                         storeSpan(AbstractSpan $span)
 * @method static bool                         storeDroppedSpanStats(AbstractSpan $span)
 * @method static int|null                     getUnfinishedSpanCount()
 * @method static bool                         setAllowedExitCode(int $expectedValue)
 * @method static int|null                     getAllowedExitCode()
 * @method static bool                         incrementUnfinishedSpanCount()
 * @method static bool                         decrementUnfinishedSpanCount()
 * @method static bool                         resetData()
 *
 * @see RepositoryContract
 */
class LaraMonitorStore extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RepositoryContract::class;
    }
}
