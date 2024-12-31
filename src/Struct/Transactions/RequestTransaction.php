<?php

namespace Nivseb\LaraMonitor\Struct\Transactions;

class RequestTransaction extends AbstractTransaction
{
    public string $method = '';

    public string $path = '';

    public ?int $responseCode = null;

    public function getName(): string
    {
        return $this->method.' '.$this->path;
    }
}
