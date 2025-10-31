<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Carbon\Carbon;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\JobCollectorContract;
use Nivseb\LaraMonitor\Exceptions\WrongEventException;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Throwable;

class JobTransactionCollector extends AbstractTransactionCollector implements JobCollectorContract
{
    /**
     * @throws WrongEventException
     */
    public function startMainAction($event): ?AbstractTransaction
    {
        try {
            if (!$event instanceof JobProcessing) {
                throw new WrongEventException(static::class, JobProcessing::class, $event::class);
            }
            $transaction = LaraMonitorStore::getTransaction();
            if ($transaction) {
                LaraMonitorSpan::startAction('run', 'app', 'handler', Carbon::now(), true);
            }
            if ($transaction instanceof JobTransaction) {
                $transaction->jobName       = $event->job->resolveName();
                $transaction->jobId         = $event->job->getJobId();
                $transaction->jobConnection = $event->job->getConnectionName();
                $transaction->jobQueue      = $event->job->getQueue();
            }

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start main action for job transaction!', $exception);

            return null;
        }
    }

    public function stopMainAction($event): ?AbstractTransaction
    {
        try {
            if (!$event instanceof JobProcessed && !$event instanceof JobFailed) {
                throw new WrongEventException(static::class, JobProcessed::class, $event::class);
            }
            $transaction = LaraMonitorStore::getTransaction();
            if ($transaction) {
                $now = Carbon::now();
                LaraMonitorSpan::stopAction($now);
                LaraMonitorSpan::startAction('terminating', 'terminate', startAt: $now, system: true);
            }
            if ($transaction instanceof JobTransaction) {
                $transaction->failed = $event instanceof JobFailed;
            }

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t stop main action for job transaction!', $exception);

            return null;
        }
    }

    protected function buildTransaction(?string $traceParent = null): AbstractTransaction
    {
        return new JobTransaction($this->getOrStartTrace($traceParent));
    }
}
