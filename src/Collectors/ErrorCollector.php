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
use Nivseb\LaraMonitor\Traits\HasLogging;
use Throwable;

class ErrorCollector implements ErrorCollectorContract
{
    use HasLogging;

    public function captureExceptionAsError(
        Throwable $exception,
        bool $handled = false,
        ?CarbonInterface $time = null
    ): ?Error {
        try {
            return $this->captureError(
                $this->buildErrorTypeFromException($exception),
                $exception->getCode(),
                $this->buildErrorMessageFromException($exception),
                $handled,
                $time,
                $this->buildAdditionalDataFromException($exception),
                $exception
            );
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t capture exception as error!', $exception);

            return null;
        }
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
        try {
            $currentEvent = LaraMonitorStore::getCurrentTraceEvent();
            if (!$currentEvent) {
                return null;
            }

            $error = new Error(
                $currentEvent,
                $type,
                $code,
                $message,
                $handled,
                $time?->clone() ?? Carbon::now(),
                $exception
            );
            foreach ($additionalData as $key => $value) {
                $error->setCustomContext($key, $value);
            }

            return $error;
        } catch (Throwable $exception) {
            $this->logForLaraMonitorFail('Can`t capture error!', $exception);

            return null;
        }
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
