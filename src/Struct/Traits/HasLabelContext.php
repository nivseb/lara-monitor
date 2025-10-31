<?php

namespace Nivseb\LaraMonitor\Struct\Traits;

trait HasLabelContext
{
    /** @var array<string, null|bool|float|int|string> */
    protected array $labels = [];

    public function getLabels(): ?array
    {
        return $this->labels ?: null;
    }

    public function setLabel(string $key, bool|float|int|string|null $data): void
    {
        $this->labels[$key] = $data;
    }
}
