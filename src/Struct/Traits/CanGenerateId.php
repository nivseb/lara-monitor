<?php

namespace Nivseb\LaraMonitor\Struct\Traits;

trait CanGenerateId
{
    /**
     * @see https://www.w3.org/TR/trace-context/#parent-id
     *
     * @param int<1, max> $length
     */
    protected function generateId(int $length): string
    {
        return bin2hex(random_bytes($length));
    }
}
