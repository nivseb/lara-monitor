<?php

namespace Nivseb\LaraMonitor\Elastic\Builder;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Nivseb\LaraMonitor\Contracts\Elastic\ElasticFormaterContract;
use Nivseb\LaraMonitor\Contracts\Elastic\ErrorBuilderContract;
use Nivseb\LaraMonitor\Struct\Error;
use Nivseb\LaraMonitor\Struct\Spans\AbstractSpan;
use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

class ErrorBuilder implements ErrorBuilderContract
{
    public function __construct(
        protected ElasticFormaterContract $formater
    ) {}

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function buildErrorRecords(AbstractTransaction $transaction, Collection $spans): array
    {
        return array_filter(
            [
                ...$this->buildErrorRecordsForList($transaction->getErrors(), $transaction),
                ...$this->buildErrorRecordsForSpans($transaction, $spans),
            ]
        );
    }

    /**
     * @param Collection<array-key, AbstractSpan> $spans
     */
    public function buildErrorRecordsForSpans(AbstractTransaction $transaction, Collection $spans): array
    {
        $errorRecords = [];
        foreach ($spans as $span) {
            $errors = $span->getErrors();
            if (!$errors) {
                continue;
            }
            $errorRecords = [
                ...$errorRecords,
                ...$this->buildErrorRecordsForList($errors, $transaction),
            ];
        }

        return $errorRecords;
    }

    /**
     * @param array<array-key, Error> $errors
     */
    protected function buildErrorRecordsForList(array $errors, AbstractTransaction $transaction): array
    {
        $errorRecords = [];
        foreach ($errors as $error) {
            $errorRecord = $this->buildErrorRecord($error, $transaction);
            if (!$errorRecord) {
                continue;
            }
            $errorRecords[] = ['error' => $errorRecord];
        }

        return $errorRecords;
    }

    protected function buildErrorRecord(Error $error, AbstractTransaction $transaction): ?array
    {
        $timestamp = $this->formater->getTimestamp($error->time);
        if ($timestamp === null) {
            return null;
        }

        $errorData = [
            'id'             => $error->id,
            'transaction_id' => $transaction->getId(),
            'parent_id'      => $error->parentEvent->getId(),
            'trace_id'       => $error->parentEvent->getTraceId(),
            'timestamp'      => $timestamp,
            'culprit'        => null,
            'exception'      => [
                'message' => $error->message,
                'type'    => $error->type,
                'code'    => $error->code,
                'handled' => $error->handled,
            ],
        ];

        if ($error->throwable) {
            $errorData['exception']['stacktrace'] = $this->mapStacktrace($error->throwable->getTrace());
            $errorData['culprit']                 = $error->throwable->getFile().':'.$error->throwable->getLine();
        }

        return $errorData;
    }

    protected function mapStacktrace(array $traces): array
    {
        $stacktrace = [];
        foreach ($traces as $trace) {
            $file         = Arr::get($trace, 'file');
            $className    = Arr::get($trace, 'class');
            $filename     = $file ? basename($file) : ($className ? null : 'anonymous');
            $stacktrace[] = [
                'function'  => Arr::get($trace, 'function', '(closure)'),
                'abs_path'  => $file,
                'filename'  => $filename,
                'lineno'    => Arr::get($trace, 'line', 0),
                'classname' => $className,
            ];
        }

        return $stacktrace;
    }
}
