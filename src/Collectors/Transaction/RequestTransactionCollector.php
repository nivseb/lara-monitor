<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Routing\Events\RouteMatched;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\RequestCollectorContract;
use Nivseb\LaraMonitor\Exceptions\WrongEventException;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

class RequestTransactionCollector extends AbstractTransactionCollector implements RequestCollectorContract
{
    /**
     * @throws WrongEventException
     */
    public function startMainAction($event): ?AbstractTransaction
    {
        if (!$event instanceof RouteMatched) {
            throw new WrongEventException(static::class, RouteMatched::class, $event::class);
        }
        $transaction = parent::startMainAction($event);
        if ($transaction instanceof RequestTransaction) {
            $transaction->method = $event->request->getMethod();
            $transaction->path   = $event->request->getPathInfo();
        }

        return $transaction;
    }

    /**
     * @throws WrongEventException
     */
    public function stopMainAction($event): ?AbstractTransaction
    {
        if (!$event instanceof RequestHandled) {
            throw new WrongEventException(static::class, RequestHandled::class, $event::class);
        }
        $transaction = parent::stopMainAction($event);
        if ($transaction instanceof RequestTransaction) {
            $transaction->responseCode = $event->response->getStatusCode();
        }

        return $transaction;
    }

    protected function buildTransaction(?string $traceParent = null): AbstractTransaction
    {
        return new RequestTransaction($this->getOrStartTrace($traceParent));
    }
}
