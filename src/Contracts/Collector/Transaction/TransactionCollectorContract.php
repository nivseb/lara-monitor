<?php

namespace Nivseb\LaraMonitor\Contracts\Collector\Transaction;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\RouteMatched;
use Laravel\Octane\Events\RequestHandled as OctaneRequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Symfony\Component\HttpFoundation\Request;

interface TransactionCollectorContract
{
    public function startTransaction(?string $traceParent): ?AbstractTransaction;

    public function startTransactionFromRequest(Request $request): ?AbstractTransaction;

    public function booted(): ?AbstractTransaction;

    /**
     * @param CommandStarting|JobProcessing|RequestReceived|RouteMatched $event
     */
    public function startMainAction($event): ?AbstractTransaction;

    /**
     * @param CommandFinished|JobFailed|JobProcessed|OctaneRequestHandled|RequestHandled $event
     */
    public function stopMainAction($event): ?AbstractTransaction;

    public function stopTransaction(): ?AbstractTransaction;

    public function setUser(string $guard, Authenticatable $user): void;

    public function unsetUser(): void;
}
