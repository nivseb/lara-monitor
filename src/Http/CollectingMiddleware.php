<?php

namespace Nivseb\LaraMonitor\Http;

use Closure;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
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
                            $span->successful   = $response->getStatusCode() < 400;
                        }

                        return $response;
                    },
                    function (mixed $reason): PromiseInterface {
                        $span = LaraMonitorSpan::stopAction();
                        if ($span instanceof HttpSpan) {
                            if ($reason instanceof BadResponseException) {
                                $span->responseCode = $reason->getResponse()->getStatusCode();
                            }
                            $span->successful = false;
                        }

                        return Create::rejectionFor($reason);
                    }
                );
        };
    }
}
