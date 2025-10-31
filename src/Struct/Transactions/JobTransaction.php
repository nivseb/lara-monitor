<?php

namespace Nivseb\LaraMonitor\Struct\Transactions;

use Illuminate\Support\Arr;

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

    public function getCustomContext(): ?array
    {
        $data = parent::getCustomContext() ?? [];
        Arr::set($data, 'job.id', $this->jobId);

        return $data;
    }

    public function getLabels(): ?array
    {
        $data = parent::getLabels() ?? [];
        Arr::set($data, 'laravel_job_id', $this->jobId);
        Arr::set($data, 'laravel_job_connection', $this->jobConnection);
        Arr::set($data, 'laravel_job_id', $this->jobId);

        return array_filter($data) ?: null;
    }
}
