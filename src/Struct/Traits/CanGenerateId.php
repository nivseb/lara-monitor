<?php

namespace Nivseb\LaraMonitor\Struct\Traits;

trait CanGenerateId
{
    protected string $id = '';

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @see https://www.w3.org/TR/trace-context/#parent-id
     *
     * @param int<1, max> $length
     */
    protected function generateId(int $length = 8): string
    {
        return bin2hex(random_bytes($length));
    }
}
