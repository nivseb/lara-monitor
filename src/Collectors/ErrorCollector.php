<?php

namespace Nivseb\LaraMonitor\Collectors;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Nivseb\LaraMonitor\Contracts\AdditionalErrorDataContract;
use Nivseb\LaraMonitor\Contracts\Collector\ErrorCollectorContract;
use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use Nivseb\LaraMonitor\Struct\Error;
use Throwable;

class ErrorCollector implements ErrorCollectorContract
{
    public function captureExceptionAsError(
        Throwable $exception,
        bool $handled = false,
        ?CarbonInterface $time = null
    ): ?Error {
        return $this->captureError(
            $this->buildErrorTypeFromException($exception),
            $exception->getCode(),
            $this->buildErrorMessageFromException($exception),
            $handled,
            $time,
            $this->buildAdditionalDataFromException($exception),
            $exception
        );
    }

    public function captureError(
        string $type,
        int|string $code,
        string $message,
        bool $handled = false,
        ?CarbonInterface $time = null,
        ?array $additionalData = null,
        ?Throwable $exception = null
    ): ?Error {
        $currentEvent = LaraMonitorStore::getCurrentTraceEvent();
        if (!$currentEvent) {
            return null;
        }

        return new Error(
            $currentEvent,
            $type,
            $code,
            $message,
            $handled,
            $time?->clone() ?? Carbon::now(),
            $additionalData,
            $exception
        );
    }

    protected function buildErrorTypeFromException(Throwable $exception): string
    {
        return Str::afterLast($exception::class, '\\');
    }

    protected function buildErrorMessageFromException(Throwable $exception): string
    {
        if ($exception instanceof ModelNotFoundException) {
            return 'Instance for '.$exception->getModel().' not found!';
        }

        return $exception->getMessage();
    }

    protected function buildAdditionalDataFromException(Throwable $exception): ?array
    {
        if ($exception instanceof AdditionalErrorDataContract) {
            return $exception->getAdditionalErrorData();
        }
        if ($exception instanceof ModelNotFoundException) {
            return ['ids' => $exception->getIds()];
        }

        return null;
    }
}
