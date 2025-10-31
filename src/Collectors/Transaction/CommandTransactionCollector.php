<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Carbon\Carbon;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\CommandCollectorContract;
use Nivseb\LaraMonitor\Exceptions\WrongEventException;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Throwable;

class CommandTransactionCollector extends AbstractTransactionCollector implements CommandCollectorContract
{
    public function startMainAction($event): ?AbstractTransaction
    {
        try {
            if (!$event instanceof CommandStarting) {
                throw new WrongEventException(static::class, CommandStarting::class, $event::class);
            }
            $transaction = LaraMonitorStore::getTransaction();
            if ($transaction) {
                LaraMonitorSpan::startAction('run', 'app', 'handler', Carbon::now(), true);
            }
            if ($transaction instanceof CommandTransaction) {
                $transaction->command = $event->command;
            }

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start main action for command transaction!', $exception);

            return null;
        }
    }

    public function stopMainAction($event): ?AbstractTransaction
    {
        try {
            if (!$event instanceof CommandFinished) {
                throw new WrongEventException(static::class, CommandFinished::class, $event::class);
            }
            $transaction = LaraMonitorStore::getTransaction();
            if ($transaction) {
                $now = Carbon::now();
                LaraMonitorSpan::stopAction($now);
                LaraMonitorSpan::startAction('terminating', 'terminate', startAt: $now, system: true);
            }
            if ($transaction instanceof CommandTransaction) {
                $transaction->exitCode = $event->exitCode;
            }

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t stop main action for command transaction!', $exception);

            return null;
        }
    }

    protected function buildTransaction(?string $traceParent = null): AbstractTransaction
    {
        return new CommandTransaction($this->getOrStartTrace($traceParent));
    }
}
