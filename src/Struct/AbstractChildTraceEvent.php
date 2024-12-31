<?php

namespace Nivseb\LaraMonitor\Struct;

use Carbon\CarbonInterface;
use Nivseb\LaraMonitor\Struct\Traits\CanGenerateId;

abstract class AbstractChildTraceEvent extends AbstractTraceEvent
{
    use CanGenerateId;

    public readonly string $id;
    public readonly AbstractTraceEvent $parentEvent;
    public ?bool $successful = null;

    protected array $errors = [];

    public function __construct(
        AbstractTraceEvent $parentEvent,
        public ?CarbonInterface $startAt = null,
        public ?CarbonInterface $finishAt = null
    ) {
        $this->parentEvent = $parentEvent;
        $this->id          = $this->generateId(8);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTrace(): AbstractTraceEvent
    {
        return $this->parentEvent->getTrace();
    }

    public function getTraceId(): string
    {
        return $this->parentEvent->getTraceId();
    }

    public function isSampled(): bool
    {
        return $this->parentEvent->isSampled();
    }

    abstract public function getName(): string;

    public function isCompleted(): bool
    {
        return null !== $this->startAt && null !== $this->finishAt;
    }

    public function hasErrors(): bool
    {
        return (bool) $this->errors;
    }

    /**
     * @return array<Error>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(Error $error): void
    {
        $this->errors[] = $error;
    }
}
