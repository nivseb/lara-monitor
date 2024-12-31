<?php

namespace Nivseb\LaraMonitor\Struct;

class User
{
    public function __construct(
        public ?string $domain = null,
        public null|int|string $id = null,
        public ?string $username = null,
        public ?string $email = null,
    ) {}
}
