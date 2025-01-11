<?php

namespace Nivseb\LaraMonitor\Services;

use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Contracts\AnalyserContract;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Nivseb\LaraMonitor\Struct\Spans\QuerySpan;
use Nivseb\LaraMonitor\Struct\Spans\RedisCommandSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\CommandTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\JobTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

class Analyser implements AnalyserContract
{
    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function analyse(AbstractTransaction $transaction, Collection $spans, ?int $allowedExitCode): void
    {
        $this->checkTransaction($transaction, $allowedExitCode);
        foreach ($spans as $span) {
            $this->checkSpan($span);
        }
    }

    protected function checkTransaction(AbstractTransaction $transaction, ?int $allowedExitCode): void
    {
        $transaction->successful = match (true) {
            $transaction instanceof RequestTransaction => $this->isRequestTransactionSuccessful(
                $transaction,
                $allowedExitCode
            ),
            $transaction instanceof JobTransaction     => $this->isJobTransactionSuccessful($transaction),
            $transaction instanceof CommandTransaction => $this->isCommandTransactionSuccessful(
                $transaction,
                $allowedExitCode
            ),
            default => null
        };
    }

    protected function checkHttpSpan(HttpSpan $span): bool
    {
        return $span->responseCode < 400;
    }

    protected function checkSpan(AbstractSpan $span): void
    {
        $span->successful = match (true) {
            $span instanceof HttpSpan => $this->checkHttpSpan($span),
            $span instanceof QuerySpan, $span instanceof RedisCommandSpan => true,
            default => null
        };
    }

    protected function isRequestTransactionSuccessful(RequestTransaction $transaction, ?int $allowedExitCode): bool
    {
        foreach ($transaction->getErrors() as $error) {
            if (!$error->handled) {
                return $transaction->responseCode < 500;
            }
        }

        if ($allowedExitCode !== null) {
            return $transaction->responseCode === $allowedExitCode;
        }

        return $transaction->responseCode < 500;
    }

    protected function isJobTransactionSuccessful(JobTransaction $transaction): bool
    {
        return !$transaction->failed;
    }

    protected function isCommandTransactionSuccessful(CommandTransaction $transaction, ?int $allowedExitCode): bool
    {
        return $transaction->exitCode === ($allowedExitCode ?? 0);
    }
}
