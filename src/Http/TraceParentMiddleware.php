<?php

namespace Nivseb\LaraMonitor\Http;

use Closure;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Psr\Http\Message\RequestInterface;

class TraceParentMiddleware
{
    public function __invoke(Closure $handler): Closure
    {
        return function (RequestInterface $request, array $options) use (&$handler) {
            $traceEvent = LaraMonitorStore::getCurrentTraceEvent();
            if ($traceEvent) {
                $request = $request->withHeader('traceparent', (string)$traceEvent->asW3CTraceParent());
            }

            return $handler($request, $options);
        };
    }
}
