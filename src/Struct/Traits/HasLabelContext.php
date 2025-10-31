<?php

namespace Nivseb\LaraMonitor\Struct\Traits;

trait HasLabelContext
{
    /** @var array<string,string|bool|int|float|null> $labels */
    protected array $labels = [];

    public function getLabels(): ?array
    {
        return $this->labels ?: null;
    }

    public function setLabel(string $key, string|bool|int|float|null $data): void
    {
        $this->labels[$key] = $data;
    }
}
