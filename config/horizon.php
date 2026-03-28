<?php

return [

    'domain' => env('HORIZON_DOMAIN'),

    'path' => 'horizon',

    'use' => 'default',

    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'environments' => [
        'production' => [
            'criterion-workers' => [
                'connection' => 'redis',
                'queue' => ['default', 'criterion'],
                'balance' => 'auto',
                'processes' => 3,
                'tries' => 3,
            ],
        ],

        'local' => [
            'criterion-workers' => [
                'connection' => 'redis',
                'queue' => ['default', 'criterion'],
                'balance' => 'auto',
                'processes' => 1,
                'tries' => 3,
            ],
        ],
    ],
];
