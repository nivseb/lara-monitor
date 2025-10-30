<?php

namespace Nivseb\LaraMonitor\Struct\Transactions;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

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

        $uri = $this->route->uri();
        if (!Str::startsWith($uri, '/')) {
            $uri = '/'.$uri;
        }

        return $this->method.' '.$uri;
    }
}
