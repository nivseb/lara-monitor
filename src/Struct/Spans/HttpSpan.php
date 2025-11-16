<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Psr\Http\Message\UriInterface;

class HttpSpan extends AbstractSpan
{
    public int $responseCode = 0;

    public function __construct(
        public string $method,
        public UriInterface $uri,
        AbstractChildTraceEvent $parentEvent,
        int $startAt,
        ?int $finishAt = null,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getHost(): string
    {
        return $this->uri->getHost();
    }

    public function getPath(): string
    {
        $path = $this->uri->getPath();

        return !$path ? '/' : urldecode($path);
    }

    public function getPort(): int
    {
        return $this->uri->getPort() ?? ($this->getScheme() === 'https' ? 443 : 80);
    }

    public function getScheme(): string
    {
        $scheme = $this->uri->getScheme();

        return !$scheme ? 'http' : $scheme;
    }

    public function getName(): string
    {
        return $this->method.' '.$this->getPath();
    }
}
