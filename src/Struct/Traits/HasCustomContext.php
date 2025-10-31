<?php

namespace Nivseb\LaraMonitor\Struct\Traits;

trait HasCustomContext
{
    /** @var array<string, mixed> */
    protected array $contextData = [];

    public function getCustomContext(): ?array
    {
        return $this->contextData ?: null;
    }

    public function setCustomContext(string $key, mixed $data): void
    {
        $this->contextData[$key] = $data;
    }

    public function addCustomContextListEntry(string $key, mixed $data): void
    {
        if (!array_key_exists($key, $this->contextData) || !is_array($this->contextData[$key])) {
            $this->contextData[$key] = [];
        }
        $this->contextData[$key][] = $data;
    }
}
