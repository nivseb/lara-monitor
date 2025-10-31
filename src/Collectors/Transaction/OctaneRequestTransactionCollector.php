<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Carbon\Carbon;
use Illuminate\Routing\Route;
use Laravel\Octane\Events\RequestHandled as OctaneRequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\OctaneRequestCollectorContract;
use Nivseb\LaraMonitor\Exceptions\WrongEventException;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\OctaneRequestTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;
use Throwable;

class OctaneRequestTransactionCollector extends AbstractTransactionCollector implements OctaneRequestCollectorContract
{
    public function startMainAction($event): ?AbstractTransaction
    {
        try {
            if (!$event instanceof RequestReceived) {
                throw new WrongEventException(static::class, RequestReceived::class, $event::class);
            }

            $transaction = LaraMonitorStore::getTransaction();
            if ($transaction) {
                LaraMonitorSpan::startAction('run', 'app', 'handler', Carbon::now(), true);
            }
            if ($transaction instanceof RequestTransaction) {
                $route                       = $event->request->route();
                $transaction->route          = $route instanceof Route ? $route : null;
                $transaction->method         = $event->request->getMethod();
                $transaction->path           = $event->request->getPathInfo();
                $transaction->fullUrl        = $event->request->fullUrl();
                $transaction->httpVersion    = $event->request->getProtocolVersion();
                $transaction->requestHeaders = $event->request->header();
                $transaction->requestCookies = $event->request->cookie();
            }

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start main action for octane request transaction!', $exception);

            return null;
        }
    }

    public function stopMainAction($event): ?AbstractTransaction
    {
        try {
            if (!$event instanceof OctaneRequestHandled) {
                throw new WrongEventException(static::class, OctaneRequestHandled::class, $event::class);
            }

            $transaction = LaraMonitorStore::getTransaction();
            if ($transaction) {
                $now = Carbon::now();
                LaraMonitorSpan::stopAction($now);
                LaraMonitorSpan::startAction('terminating', 'terminate', startAt: $now, system: true);
            }
            if (!$transaction instanceof RequestTransaction) {
                return $transaction;
            }
            $transaction->responseCode    = $event->response->getStatusCode();
            $transaction->responseHeaders = $event->response->headers->all();
            if (!$transaction->route) {
                $route              = $event->request->route();
                $transaction->route = $route instanceof Route ? $route : null;
            }
            $transaction->method ??= $event->request->getMethod();
            $transaction->path ??= $event->request->getPathInfo();
            $transaction->fullUrl ??= $event->request->fullUrl();
            $transaction->httpVersion ??= $event->request->getProtocolVersion();
            $transaction->requestHeaders ??= $event->request->header();
            $transaction->requestCookies ??= $event->request->cookie();

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t stop main action for octane request transaction!', $exception);

            return null;
        }
    }

    protected function buildTransaction(?string $traceParent = null): AbstractTransaction
    {
        return new OctaneRequestTransaction($this->getOrStartTrace($traceParent));
    }
}
