<?php

namespace Nivseb\LaraMonitor\Struct\Transactions;

class CommandTransaction extends AbstractTransaction
{
    public string $command = '';

    public ?int $exitCode = null;

    public function getName(): string
    {
        return $this->command ?: 'Unknown';
    }
}
