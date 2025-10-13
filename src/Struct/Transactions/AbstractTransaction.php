<?php

namespace Nivseb\LaraMonitor\Struct\Transactions;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Nivseb\LaraMonitor\Struct\Tracing\AbstractTrace;
use Nivseb\LaraMonitor\Struct\Traits\HasCustomContext;
use Nivseb\LaraMonitor\Struct\User;

abstract class AbstractTransaction extends AbstractChildTraceEvent
{
    use HasCustomContext;

    protected ?User $user = null;

    public function __construct(
        AbstractTrace    $parentEvent,
        ?CarbonInterface $startAt = null,
        ?CarbonInterface $finishAt = null
    )
    {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
