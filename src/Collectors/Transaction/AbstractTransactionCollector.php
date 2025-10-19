<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
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
use Nivseb\LaraMonitor\Traits\HasLogging;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

abstract class AbstractTransactionCollector implements TransactionCollectorContract
{
    use HasLogging;

    public function startTransaction(?string $traceParent = null): ?AbstractTransaction
    {
        try {
            $now                  = Carbon::now();
            $transaction          = $this->buildTransaction($traceParent);
            $transaction->startAt = $now;
            LaraMonitorStore::setTransaction($transaction, new Collection());
            LaraMonitorSpan::startAction('booting', 'boot', startAt: $now, system: true);

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start transaction in `'.static::class.'` !', $exception);

            return null;
        }
    }

    public function startTransactionFromRequest(Request $request): ?AbstractTransaction
    {
        try {
            $traceParent = null;
            if (!Config::get('lara-monitor.ignoreExternalTrace')) {
                $traceParent = $request->headers->get('traceparent');
            }

            return $this->startTransaction($traceParent);
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start transaction from request in `'.static::class.'` !', $exception);

            return null;
        }
    }

    public function booted(): ?AbstractTransaction
    {
        try {
            $transaction = LaraMonitorStore::getTransaction();
            if ($transaction) {
                LaraMonitorSpan::stopAction(Carbon::now());
            }

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t mark transaction as booted in `'.static::class.'` !', $exception);

            return null;
        }
    }

    public function stopTransaction(): ?AbstractTransaction
    {
        try {
            $transaction = LaraMonitorStore::getTransaction();
            if ($transaction) {
                $now = Carbon::now();
                LaraMonitorSpan::stopAction($now);
                $transaction->finishAt = $now;
            }

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t stop transaction  in `'.static::class.'` !', $exception);

            return null;
        }
    }

    public function setUser(string $guard, Authenticatable $user): void
    {
        try {
            $transaction = LaraMonitorStore::getTransaction();
            if (!$transaction) {
                return;
            }

            $transaction->setUser(LaraMonitorMapper::buildUserFromAuthenticated($guard, $user));
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start transaction in `'.static::class.'` !', $exception);
        }
    }

    public function unsetUser(): void
    {
        try {
            $transaction = LaraMonitorStore::getTransaction();
            if (!$transaction) {
                return;
            }
            $transaction->setUser(null);
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start transaction in `'.static::class.'` !', $exception);
        }
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
