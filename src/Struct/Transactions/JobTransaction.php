<?php

namespace Nivseb\LaraMonitor\Struct\Transactions;

use Illuminate\Support\Arr;

class JobTransaction extends AbstractTransaction
{
    public string $jobId = '';
    public string $jobName = '';

    public bool $failed = false;

    public function getName(): string
    {
        return $this->jobName;
    }

    public function getCustomData(): ?array
    {
        if (!$this->jobId) {
            return parent::getCustomContext();
        }
        $data = parent::getCustomContext() ?? [];
        Arr::set($data, 'job.id', $this->jobId);
        return $data;
    }
}
