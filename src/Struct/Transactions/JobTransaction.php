<?php

namespace Nivseb\LaraMonitor\Struct\Transactions;

class JobTransaction extends AbstractTransaction
{
    public string $jobName = '';

    public bool $failed = false;

    public function getName(): string
    {
        return $this->jobName;
    }
}
