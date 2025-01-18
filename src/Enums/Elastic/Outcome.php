<?php

namespace Nivseb\LaraMonitor\Enums\Elastic;

enum Outcome: string
{
    case Success = 'success';
    case Failure = 'failure';
}
