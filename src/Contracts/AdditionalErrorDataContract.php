<?php

namespace Nivseb\LaraMonitor\Contracts;

use Throwable;

interface AdditionalErrorDataContract extends Throwable
{
    public function getAdditionalErrorData(): ?array;
}
