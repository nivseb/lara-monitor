<?php

namespace Nivseb\LaraMonitor\Struct\Elastic;

class TypeData
{
    public function __construct(
        public string $type,
        public ?string $subType = null,
        public ?string $action = null
    ) {}
}
