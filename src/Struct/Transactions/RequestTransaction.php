<?php

namespace Nivseb\LaraMonitor\Struct\Transactions;

use Illuminate\Routing\Route;

class RequestTransaction extends AbstractTransaction
{
    public ?Route $route = null;

    public string $method = '';

    public string $path = '';

    public ?int $responseCode = null;

    public function getName(): string
    {
        if (!$this->route) {
            return $this->method.' '.$this->path;
        }

        return $this->method.' /'.$this->route->uri();
    }
}
