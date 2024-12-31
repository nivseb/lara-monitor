<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Carbon\Carbon;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\TransactionCollectorContract;
use Nivseb\LaraMonitor\Exceptions\InvalidTraceFormatException;
use Nivseb\LaraMonitor\Facades\LaraMonitorMapper;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Tracing\ExternalTrace;
use Nivseb\LaraMonitor\Struct\Tracing\StartTrace;
use Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Laravel\Octane\Events\RequestHandled as OctaneRequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTransactionCollector implements TransactionCollectorContract
{
    public function startTransaction(?string $traceParent = null): ?AbstractTransaction
    {
        $now                  = Carbon::now();
        $transaction          = $this->buildTransaction($traceParent);
        $transaction->startAt = $now;
        LaraMonitorStore::setTransaction($transaction, new Collection());
        LaraMonitorSpan::startAction('booting', 'boot', startAt: $now, system: true);

        return $transaction;
    }

    public function startTransactionFromRequest(Request $request): ?AbstractTransaction
    {
        $traceParent = null;
        if (!Config::get('lara-monitor.ignoreExternalTrace')) {
            $traceParent = $request->headers->get('traceparent');
        }

        return $this->startTransaction($traceParent);
    }

    public function booted(): ?AbstractTransaction
    {
        $transaction = LaraMonitorStore::getTransaction();
        if ($transaction) {
            LaraMonitorSpan::stopAction(Carbon::now());
        }

        return $transaction;
    }

    public function stopTransaction(): ?AbstractTransaction
    {
        $transaction = LaraMonitorStore::getTransaction();
        if ($transaction) {
            $now = Carbon::now();
            LaraMonitorSpan::stopAction($now);
            $transaction->finishAt = $now;
        }

        return $transaction;
    }

    public function setUser(string $guard, Authenticatable $user): void
    {
        $transaction = LaraMonitorStore::getTransaction();
        if (!$transaction) {
            return;
        }

        $transaction->setUser(LaraMonitorMapper::buildUserFromAuthenticated($guard, $user));
    }

    public function unsetUser(): void
    {
        $transaction = LaraMonitorStore::getTransaction();
        if (!$transaction) {
            return;
        }
        $transaction->setUser(null);
    }

    public function startMainAction($event): ?AbstractTransaction {
        $transaction = LaraMonitorStore::getTransaction();
        if ($transaction) {
            LaraMonitorSpan::startAction('run', 'app', 'handler', Carbon::now(), true);
        }

        return $transaction;
    }

    public function stopMainAction($event): ?AbstractTransaction {
        $transaction = LaraMonitorStore::getTransaction();
        if ($transaction) {
            $now = Carbon::now();
            LaraMonitorSpan::stopAction($now);
            LaraMonitorSpan::startAction('terminating', 'terminate', startAt: $now, system: true);
        }

        return $transaction;
    }

    abstract protected function buildTransaction(?string $traceParent = null): AbstractTransaction;

    protected function getOrStartTrace(?string $traceParent = null): AbstractTrace
    {
        if (!$traceParent) {
            return $this->startTrace();
        }

        try {
            $w3cTrace = W3CTraceParent::createFromString($traceParent);
        } catch (InvalidTraceFormatException) {
            return $this->startTrace();
        }

        return new ExternalTrace($w3cTrace);
    }

    protected function startTrace(): StartTrace
    {
        $sampleRate = round((float) Config::get('lara-monitor.sampleRate', 1.0), 4);

        return new StartTrace($this->shouldBeSampled($sampleRate), $sampleRate);
    }

    protected function shouldBeSampled(float $sampleRate): bool
    {
        return mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax() <= $sampleRate;
    }
}
