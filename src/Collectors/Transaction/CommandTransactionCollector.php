<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\CommandCollectorContract;
use Nivseb\LaraMonitor\Exceptions\WrongEventException;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;

class CommandTransactionCollector extends AbstractTransactionCollector implements CommandCollectorContract
{
    /**
     * @throws WrongEventException
     */
    public function startMainAction($event): ?AbstractTransaction
    {
        if (!$event instanceof CommandStarting) {
            throw new WrongEventException(static::class, CommandStarting::class, $event::class);
        }
        $transaction = parent::startMainAction($event);
        if ($transaction instanceof CommandTransaction) {
            $transaction->command = $event->command;
        }

        return $transaction;
    }

    /**
     * @throws WrongEventException
     */
    public function stopMainAction($event): ?AbstractTransaction
    {
        if (!$event instanceof CommandFinished) {
            throw new WrongEventException(static::class, CommandFinished::class, $event::class);
        }
        $transaction = parent::stopMainAction($event);
        if ($transaction instanceof CommandTransaction) {
            $transaction->exitCode = $event->exitCode;
        }

        return $transaction;
    }

    protected function buildTransaction(?string $traceParent = null): AbstractTransaction
    {
        return new CommandTransaction($this->getOrStartTrace($traceParent));
    }
}
