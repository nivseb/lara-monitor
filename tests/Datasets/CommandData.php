<?php

namespace Tests\Datasets;

dataset(
    'error exit codes',
    [
        'General Error'                  => [1],
        'Misuse of shell built-ins'      => [2],
        'Command cannot execute'         => [126],
        'Command not found'              => [127],
        'Invalid exit argument'          => [128],
        'Script terminated by Control-C' => [130],
    ]
);
