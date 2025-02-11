<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\JobCollectorContract;
use Nivseb\LaraMonitor\Exceptions\WrongEventException;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;

class JobTransactionCollector extends AbstractTransactionCollector implements JobCollectorContract
{
    /**
     * @throws WrongEventException
     */
    public function startMainAction($event): ?AbstractTransaction
    {
        if (!$event instanceof JobProcessing) {
            throw new WrongEventException(static::class, JobProcessing::class, $event::class);
        }
        $transaction = parent::startMainAction($event);
        if ($transaction instanceof JobTransaction) {
            $transaction->jobName = $event->job->resolveName();
        }

        return $transaction;
    }

    /**
     * @throws WrongEventException
     */
    public function stopMainAction($event): ?AbstractTransaction
    {
        if (!$event instanceof JobProcessed && !$event instanceof JobFailed) {
            throw new WrongEventException(static::class, JobProcessed::class, $event::class);
        }
        $transaction = parent::stopMainAction($event);
        if ($transaction instanceof JobTransaction) {
            $transaction->failed = $event instanceof JobFailed;
        }

        return $transaction;
    }

    protected function buildTransaction(?string $traceParent = null): AbstractTransaction
    {
        return new JobTransaction($this->getOrStartTrace($traceParent));
    }
}
