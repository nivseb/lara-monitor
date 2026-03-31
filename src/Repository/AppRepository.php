<?php

namespace Nivseb\LaraMonitor\Repository;

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Contracts\RepositoryContract;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\AbstractTraceEvent;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Spans\DroppedSpanStats;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Throwable;

class AppRepository implements RepositoryContract
{
    protected const TRANSACTION_KEY = 'lara-monitor.transaction';
    protected const CURRENT_TRACE_EVENT_KEY = 'lara-monitor.trace.event.current';
    protected const SPAN_LIST_KEY = 'lara-monitor.span.list';
    protected const UNFINISHED_SPANS_KEY = 'lara-monitor.span.unfinished';
    protected const SPAN_STATS_KEY = 'lara-monitor.span.stats';
    protected const ALLOWED_EXIT_CODE_KEY = 'lara-monitor.exit.allowed';

    public function getTransaction(): ?AbstractTransaction
    {
        return $this->getData(static::TRANSACTION_KEY);
    }

    /**
     * @return ?Collection<array-key, AbstractSpan>
     */
    public function getSpanList(): ?Collection
    {
        return $this->getData(static::SPAN_LIST_KEY);
    }

    public function getDroppedSpanStats(string $hash): ?DroppedSpanStats
    {
        return Arr::get($this->getDroppedSpanStatsList() ?? [], $hash);
    }

    /**
     * @return ?array<string,DroppedSpanStats>
     */
    public function getDroppedSpanStatsList(): ?array
    {
        return $this->getData(static::SPAN_STATS_KEY);
    }

    public function getUnfinishedSpanCount(): ?int
    {
        return $this->getData(static::UNFINISHED_SPANS_KEY);
    }

    public function getCurrentTraceEvent(): ?AbstractChildTraceEvent
    {
        return $this->getData(static::CURRENT_TRACE_EVENT_KEY);
    }

    public function getAllowedExitCode(): ?int
    {
        return $this->getData(static::ALLOWED_EXIT_CODE_KEY);
    }

    public function resetData(): bool
    {
        return $this->setData(static::TRANSACTION_KEY, null)
            && $this->setData(static::CURRENT_TRACE_EVENT_KEY, null)
            && $this->setData(static::SPAN_LIST_KEY, null)
            && $this->setData(static::SPAN_STATS_KEY, null)
            && $this->setData(static::UNFINISHED_SPANS_KEY, null)
            && $this->setData(static::ALLOWED_EXIT_CODE_KEY, null);
    }

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function setTransaction(AbstractTransaction $transaction, Collection $spans): bool
    {
        $result = $this->setData(static::TRANSACTION_KEY, $transaction)
            && $this->setCurrentTraceEvent($transaction)
            && $this->setCurrentSpanList($spans)
            && $this->setAllowedExitCode(null);

        if (!$result) {
            $this->resetData();

            return false;
        }

        return true;
    }

    public function storeSpan(AbstractSpan $span): bool
    {
        $spans = $this->getSpanList();
        if (!$spans) {
            return false;
        }
        $spans->add($span);

        return true;
    }

    public function storeDroppedSpanStats(DroppedSpanStats $stats): bool
    {
        $list = $this->getDroppedSpanStatsList() ?? [];
        $list[$stats->hash] = $stats;
        return $this->setData(static::SPAN_STATS_KEY, $list);
    }

    public function setAllowedExitCode(?int $expectedValue): bool
    {
        return $this->setData(static::ALLOWED_EXIT_CODE_KEY, $expectedValue);
    }

    public function setCurrentTraceEvent(AbstractTraceEvent $traceEvent): bool
    {
        if (!$traceEvent instanceof AbstractChildTraceEvent) {
            $this->setData(static::CURRENT_TRACE_EVENT_KEY, static::getTransaction());

            return false;
        }

        return $this->setData(static::CURRENT_TRACE_EVENT_KEY, $traceEvent);
    }

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    protected function setCurrentSpanList(Collection $spans): bool
    {
        return $this->setData(static::SPAN_LIST_KEY, $spans);
    }

    public function incrementUnfinishedSpanCount(): bool
    {
        return $this->setData(static::UNFINISHED_SPANS_KEY, $this->getUnfinishedSpanCount() + 1);
    }

    public function decrementUnfinishedSpanCount(): bool
    {
        return $this->setData(
            static::UNFINISHED_SPANS_KEY,
            max($this->getUnfinishedSpanCount() - 1, 0)
        );
    }

    protected function getData(string $key): mixed
    {
        try {
            return Container::getInstance()->get($key);
        } catch (Throwable) {
            return null;
        }
    }

    protected function setData(string $key, mixed $data): bool
    {
        try {
            Container::getInstance()->instance($key, $data);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
