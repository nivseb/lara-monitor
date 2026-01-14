<?php

namespace Nivseb\LaraMonitor\Collectors\Transaction;

use Carbon\Carbon;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Nivseb\LaraMonitor\Contracts\Collector\Transaction\RequestCollectorContract;
use Nivseb\LaraMonitor\Exceptions\WrongEventException;
use Nivseb\LaraMonitor\Facades\LaraMonitorSpan;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;
use Nivseb\LaraMonitor\Struct\Transactions\RequestTransaction;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class RequestTransactionCollector extends AbstractTransactionCollector implements RequestCollectorContract
{
    public function startMainAction($event): ?AbstractTransaction
    {
        try {
            if (!$event instanceof RouteMatched) {
                throw new WrongEventException(static::class, RouteMatched::class, $event::class);
            }
            $transaction = LaraMonitorStore::getTransaction();
            if ($transaction) {
                LaraMonitorSpan::startAction('run', 'app', 'handler', Carbon::now(), true);
            }
            if ($transaction instanceof RequestTransaction) {
                $transaction->route          = $event->route;
                $transaction->method         = $event->request->getMethod();
                $transaction->path           = $event->request->getPathInfo();
                $transaction->fullUrl        = $event->request->fullUrl();
                $transaction->httpVersion    = $event->request->getProtocolVersion();
                $transaction->requestHeaders = $event->request->header();
                $transaction->requestCookies = $event->request->cookie();
            }

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t start main action for request transaction!', $exception);

            return null;
        }
    }

    public function stopMainAction($event): ?AbstractTransaction
    {
        try {
            if (!$event instanceof RequestHandled) {
                throw new WrongEventException(static::class, RequestHandled::class, $event::class);
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
                $route = $event->request->route();
                $transaction->route ??= $route instanceof Route ? $route : null;
            }
            $transaction->method ??= $event->request->getMethod();
            $transaction->path ??= $event->request->getPathInfo();
            $transaction->fullUrl ??= $event->request->fullUrl();
            $transaction->httpVersion ??= $event->request->getProtocolVersion();
            $transaction->requestHeaders ??= $event->request->header();
            $transaction->requestCookies ??= $event->request->cookie();

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t stop main action for request transaction!', $exception);

            return null;
        }
    }

    public function startTransactionFromRequest(Request $request): ?AbstractTransaction
    {
        try {
            $transaction = parent::startTransactionFromRequest($request);
            if (!$transaction instanceof RequestTransaction) {
                return $transaction;
            }
            $transaction->method = $request->getMethod();
            $transaction->path   = $request->getPathInfo();

            return $transaction;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail(
                'Can`t fill transaction with request data in `'.static::class.'` !',
                $exception
            );

            throw $exception;

            return null;
        }
    }

    protected function buildTransaction(?string $traceParent = null): AbstractTransaction
    {
        return new RequestTransaction($this->getOrStartTrace($traceParent));
    }
}
