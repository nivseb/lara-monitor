<?php

namespace Tests\Datasets;

dataset(
    'response codes for request transactions',
    [
        'OK'                              => [200],
        'Created'                         => [201],
        'Accepted'                        => [202],
        'No Content'                      => [204],
        'Reset Content'                   => [205],
        'Partial Content'                 => [206],
        'Multiple Choices'                => [300],
        'Moved Permanently'               => [301],
        'Found'                           => [302],
        'See Other'                       => [303],
        'Not Modified'                    => [304],
        'Use Proxy'                       => [305],
        'Switch Proxy'                    => [306],
        'Temporary Redirect'              => [307],
        'Permanent Redirect'              => [308],
        'Bad Request'                     => [400],
        'Unauthorized'                    => [401],
        'Payment Required'                => [402],
        'Forbidden'                       => [403],
        'Not Found'                       => [404],
        'Method Not Allowed'              => [405],
        'Not Acceptable'                  => [406],
        'Proxy Authentication Required'   => [407],
        'Request Timeout'                 => [408],
        'Conflict'                        => [409],
        'Gone'                            => [410],
        'Length Required'                 => [411],
        'Precondition Failed'             => [412],
        'Payload Too Large'               => [413],
        'URI Too Long'                    => [414],
        'Unsupported Media Type'          => [415],
        'Range Not Satisfiable'           => [416],
        'Expectation Failed'              => [417],
        'Misdirected Request'             => [421],
        'Unprocessable Content'           => [422],
        'Too Early'                       => [425],
        'Upgrade Required'                => [426],
        'Precondition Required'           => [428],
        'Too Many Requests'               => [429],
        'Request Header Fields Too Large' => [431],
        'Internal Server Error'           => [500],
        'Not Implemented'                 => [501],
        'Bad Gateway'                     => [502],
        'Service Unavailable'             => [503],
        'Gateway Timeout'                 => [504],
        'HTTP Version Not Supported'      => [505],
        'Variant Also Negotiates'         => [506],
        'Not Extended'                    => [510],
        'Network Authentication Required' => [511],
    ]
);

dataset(
    'successful response codes for request transactions',
    [
        'OK'                              => [200],
        'Created'                         => [201],
        'Accepted'                        => [202],
        'No Content'                      => [204],
        'Reset Content'                   => [205],
        'Partial Content'                 => [206],
        'Multiple Choices'                => [300],
        'Moved Permanently'               => [301],
        'Found'                           => [302],
        'See Other'                       => [303],
        'Not Modified'                    => [304],
        'Use Proxy'                       => [305],
        'Switch Proxy'                    => [306],
        'Temporary Redirect'              => [307],
        'Permanent Redirect'              => [308],
        'Bad Request'                     => [400],
        'Unauthorized'                    => [401],
        'Payment Required'                => [402],
        'Forbidden'                       => [403],
        'Not Found'                       => [404],
        'Method Not Allowed'              => [405],
        'Not Acceptable'                  => [406],
        'Proxy Authentication Required'   => [407],
        'Request Timeout'                 => [408],
        'Conflict'                        => [409],
        'Gone'                            => [410],
        'Length Required'                 => [411],
        'Precondition Failed'             => [412],
        'Payload Too Large'               => [413],
        'URI Too Long'                    => [414],
        'Unsupported Media Type'          => [415],
        'Range Not Satisfiable'           => [416],
        'Expectation Failed'              => [417],
        'Misdirected Request'             => [421],
        'Unprocessable Content'           => [422],
        'Too Early'                       => [425],
        'Upgrade Required'                => [426],
        'Precondition Required'           => [428],
        'Too Many Requests'               => [429],
        'Request Header Fields Too Large' => [431],
    ]
);

dataset(
    'unsuccessful response codes for request transactions',
    [
        'Internal Server Error'           => [500],
        'Not Implemented'                 => [501],
        'Bad Gateway'                     => [502],
        'Service Unavailable'             => [503],
        'Gateway Timeout'                 => [504],
        'HTTP Version Not Supported'      => [505],
        'Variant Also Negotiates'         => [506],
        'Not Extended'                    => [510],
        'Network Authentication Required' => [511],
    ]
);

dataset(
    'response codes for http span',
    [
        'OK'                              => [200],
        'Created'                         => [201],
        'Accepted'                        => [202],
        'No Content'                      => [204],
        'Reset Content'                   => [205],
        'Partial Content'                 => [206],
        'Multiple Choices'                => [300],
        'Moved Permanently'               => [301],
        'Found'                           => [302],
        'See Other'                       => [303],
        'Not Modified'                    => [304],
        'Use Proxy'                       => [305],
        'Switch Proxy'                    => [306],
        'Temporary Redirect'              => [307],
        'Permanent Redirect'              => [308],
        'Bad Request'                     => [400],
        'Unauthorized'                    => [401],
        'Payment Required'                => [402],
        'Forbidden'                       => [403],
        'Not Found'                       => [404],
        'Method Not Allowed'              => [405],
        'Not Acceptable'                  => [406],
        'Proxy Authentication Required'   => [407],
        'Request Timeout'                 => [408],
        'Conflict'                        => [409],
        'Gone'                            => [410],
        'Length Required'                 => [411],
        'Precondition Failed'             => [412],
        'Payload Too Large'               => [413],
        'URI Too Long'                    => [414],
        'Unsupported Media Type'          => [415],
        'Range Not Satisfiable'           => [416],
        'Expectation Failed'              => [417],
        'Misdirected Request'             => [421],
        'Unprocessable Content'           => [422],
        'Too Early'                       => [425],
        'Upgrade Required'                => [426],
        'Precondition Required'           => [428],
        'Too Many Requests'               => [429],
        'Request Header Fields Too Large' => [431],
        'Internal Server Error'           => [500],
        'Not Implemented'                 => [501],
        'Bad Gateway'                     => [502],
        'Service Unavailable'             => [503],
        'Gateway Timeout'                 => [504],
        'HTTP Version Not Supported'      => [505],
        'Variant Also Negotiates'         => [506],
        'Not Extended'                    => [510],
        'Network Authentication Required' => [511],
    ]
);

dataset(
    'successful response codes for http span',
    [
        'OK'                 => [200],
        'Created'            => [201],
        'Accepted'           => [202],
        'No Content'         => [204],
        'Reset Content'      => [205],
        'Partial Content'    => [206],
        'Multiple Choices'   => [300],
        'Moved Permanently'  => [301],
        'Found'              => [302],
        'See Other'          => [303],
        'Not Modified'       => [304],
        'Use Proxy'          => [305],
        'Switch Proxy'       => [306],
        'Temporary Redirect' => [307],
        'Permanent Redirect' => [308],
    ]
);

dataset(
    'unsuccessful response codes for http span',
    [
        'Internal Server Error'           => [500],
        'Not Implemented'                 => [501],
        'Bad Gateway'                     => [502],
        'Service Unavailable'             => [503],
        'Gateway Timeout'                 => [504],
        'HTTP Version Not Supported'      => [505],
        'Variant Also Negotiates'         => [506],
        'Not Extended'                    => [510],
        'Network Authentication Required' => [511],
        'Bad Request'                     => [400],
        'Unauthorized'                    => [401],
        'Payment Required'                => [402],
        'Forbidden'                       => [403],
        'Not Found'                       => [404],
        'Method Not Allowed'              => [405],
        'Not Acceptable'                  => [406],
        'Proxy Authentication Required'   => [407],
        'Request Timeout'                 => [408],
        'Conflict'                        => [409],
        'Gone'                            => [410],
        'Length Required'                 => [411],
        'Precondition Failed'             => [412],
        'Payload Too Large'               => [413],
        'URI Too Long'                    => [414],
        'Unsupported Media Type'          => [415],
        'Range Not Satisfiable'           => [416],
        'Expectation Failed'              => [417],
        'Misdirected Request'             => [421],
        'Unprocessable Content'           => [422],
        'Too Early'                       => [425],
        'Upgrade Required'                => [426],
        'Precondition Required'           => [428],
        'Too Many Requests'               => [429],
        'Request Header Fields Too Large' => [431],
    ]
);
