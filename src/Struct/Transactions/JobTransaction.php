<?php

namespace Nivseb\LaraMonitor\Struct\Transactions;

class JobTransaction extends AbstractTransaction
{
    public string $jobId         = '';
    public string $jobConnection = '';
    public string $jobQueue      = '';
    public string $jobName       = '';

    public bool $failed = false;

    public function getName(): string
    {
        return $this->jobName;
    }
}
