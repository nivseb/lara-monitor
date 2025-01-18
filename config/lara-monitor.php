<?php

return [
    'enabled'             => (bool) env('LARA_MONITOR_ENABLED', false),
    'sampleRate'          => 1.0, // 1.0 => 100%
    'ignoreExternalTrace' => false,
    'service'             => [
        'id'        => md5(env('APP_NAME', 'Laravel').'_'.env('APP_ENV', 'production')),
        'name'      => env('APP_NAME', 'Laravel'),
        'env'       => env('APP_ENV', 'production'),
        'version'   => env('APP_VERSION'),
        'agentName' => 'lara-monitor',
    ],
    'instance' => [
        'name' => md5(env('APP_NAME', 'Laravel').'_'.env('APP_ENV', 'production').gethostname()),
        /*
         * @see https://github.com/elastic/apm/blob/main/specs/agents/metadata.md
         */
        'hostname'    => null,
        'containerId' => env('CONTAINER_ID'),
    ],
    'feature' => [
        'database' => [
            'enabled' => (bool) env('LARA_MONITOR_DB_ENABLED', true),
        ],
        'http' => [
            'enabled'     => (bool) env('LARA_MONITOR_HTTP_ENABLED', true),
            'traceParent' => true,
            'collecting'  => [
                'events'     => true,
                'middleware' => false,
            ],
        ],
        'redis' => [
            // important to enable redis events too => Redis::enableEvents() to use this feature
            'enabled' => env('LARA_MONITOR_REDIS_ENABLED', false),
        ],
        'auth' => [
            'enabled' => env('LARA_MONITOR_AUTH_ENABLED', true),
        ],
    ],
    'elasticApm' => [
        'enabled'   => (bool) env('ELASTIC_APM_URL', ''),
        'apmServer' => env('ELASTIC_APM_URL', ''),
        'meta'      => [
            /*
             * Elastic APM Meta Data
             * @see https://www.elastic.co/guide/en/apm/guide/current/data-model-metadata.html
             */
            // labels that are send as metadata to elastic apm
            'labels' => null,
            // network information that are send as metadata to elastic apm
            'network' => ['connection' => ['type' => null]],
            // cloud metadata send to elastic apm
            'cloud' => [
                'account'           => ['id' => null, 'name' => null],
                'availability_zone' => null,
                'instance'          => ['id' => null, 'name' => null],
                'machine'           => ['type' => null],
                'project'           => ['id' => null, 'name' => null],
                'provider'          => '',
                'region'            => null,
                'service'           => ['name' => null],
            ],
            // kubernetes data send to elastic apm
            'kubernetes' => [
                'namespace' => null,
                'node'      => ['name' => null],
                'pod'       => ['name' => null, 'uid' => null],
            ],
        ],
    ],
];
