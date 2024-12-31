<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Laravel\Octane\Events\RequestHandled as OctaneRequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\OctaneRequestCollectorContract;
use Nivseb\LaraMonitor\Exceptions\WrongEventException;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\OctaneRequestTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;

class OctaneRequestTransactionCollector extends AbstractTransactionCollector implements OctaneRequestCollectorContract
{
    /**
     * @throws WrongEventException
     */
    public function startMainAction($event): ?AbstractTransaction
    {
        if (!$event instanceof RequestReceived) {
            throw new WrongEventException(static::class, RequestReceived::class, $event::class);
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
        if (!$event instanceof OctaneRequestHandled) {
            throw new WrongEventException(static::class, OctaneRequestHandled::class, $event::class);
        }
        $transaction = parent::stopMainAction($event);
        if ($transaction instanceof RequestTransaction) {
            $transaction->responseCode = $event->response->getStatusCode();
        }

        return $transaction;
    }

    protected function buildTransaction(?string $traceParent = null): AbstractTransaction
    {
        return new OctaneRequestTransaction($this->getOrStartTrace($traceParent));
    }
}
