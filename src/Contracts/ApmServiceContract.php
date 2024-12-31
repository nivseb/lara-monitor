<?php

namespace Nivseb\LaraMonitor\Contracts;

interface ApmServiceContract
{
    public function getVersion(): ?string;

    public function getAgentName(): ?string;

    public function allowErrorResponse(int $allowedExitCode): void;

    public function finishCurrentTransaction(): bool;
}
