<?php

namespace Tests\Component\Feature;

use Nivseb\LaraMonitor\Facades\LaraMonitorStore;
use function Swoole\Coroutine\Http\get;

test(
    'Test Transaction for Request is created',
    function () {
        $response = get('/');

        $response->assertStatus(200);

        dd(LaraMonitorStore::getTransaction());
    }
);
