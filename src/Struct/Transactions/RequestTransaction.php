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

    public ?array $responseHeaders = null;

    public ?string $fullUrl = null;

    public ?string $httpVersion = null;

    public ?array $requestHeaders = null;
    public ?array $requestCookies = null;

    public function getName(): string
    {
        if (!$this->method) {
            return 'Unknown';
        }

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
