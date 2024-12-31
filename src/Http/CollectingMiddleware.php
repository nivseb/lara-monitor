<?php

namespace Nivseb\LaraMonitor\Http;

use Closure;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Spans\HttpSpan;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CollectingMiddleware
{
    public function __invoke(Closure $handler): Closure
    {
        return function (RequestInterface $request, array $options) use (&$handler) {
            LaraMonitorSpan::startHttpAction($request);
            return $handler($request, $options)
                ->then(
                    function (ResponseInterface $response): ResponseInterface {
                        $span = LaraMonitorSpan::stopAction();
                        if ($span instanceof HttpSpan) {
                            $span->responseCode = $response->getStatusCode();
                        }

                        return $response;
                    }
                );
        };
    }
}
