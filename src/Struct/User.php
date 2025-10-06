<?php

namespace Nivseb\LaraMonitor\Struct;

class User
{
    public function __construct(
        public ?string $domain = null,
        public int|string|null $id = null,
        public ?string $username = null,
        public ?string $email = null,
    ) {}
}
