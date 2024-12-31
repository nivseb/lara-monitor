<?php

namespace Nivseb\LaraMonitor\Facades;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Facade;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\TransactionCollectorContract;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Laravel\Octane\Events\RequestHandled as OctaneRequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method static AbstractTransaction|null startTransaction(Container $app, ?string $traceParent)
 * @method static AbstractTransaction|null startTransactionFromRequest(Request $request)
 * @method static AbstractTransaction|null booted()
 * @method static AbstractTransaction|null startMainAction(RouteMatched|RequestReceived|CommandStarting|JobProcessing $event)
 * @method static AbstractTransaction|null stopMainAction(RequestHandled|OctaneRequestHandled|CommandFinished|JobProcessed|JobFailed $event)
 * @method static AbstractTransaction|null stopTransaction()
 * @method static void                     setUser(string $guard, Authenticatable $user)
 * @method static void                     unsetUser()
 *
 * @see TransactionCollectorContract
 */
class LaraMonitorTransaction extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TransactionCollectorContract::class;
    }
}
