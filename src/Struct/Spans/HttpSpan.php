<?php

namespace Nivseb\LaraMonitor\Struct\Spans;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\AbstractChildTraceEvent;
use Psr\Http\Message\UriInterface;

class HttpSpan extends AbstractSpan
{
    public string $scheme    = '';
    public string $host      = '';
    public ?int $port        = null;
    public int $responseCode = 0;

    /** @deprecated */
    public UriInterface $uri;

    public function __construct(
        public string $method,
        public string $path,
        AbstractChildTraceEvent $parentEvent,
        CarbonInterface $startAt,
        ?CarbonInterface $finishAt = null,
    ) {
        parent::__construct($parentEvent, $startAt, $finishAt);
    }

    public function getName(): string
    {
        return $this->method.' '.$this->path;
    }
}
