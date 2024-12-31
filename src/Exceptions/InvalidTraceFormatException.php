<?php

namespace Nivseb\LaraMonitor\Exceptions;

class InvalidTraceFormatException extends AbstractException
{
    public function __construct()
    {
        parent::__construct('Invalid trace format!');
    }
}
