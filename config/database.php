<?php

return [
    'redis' => [
        'client' => 'predis',
        'default' => [
            'host' => env('REDIS_HOST', '100.68.122.24'),
            'port' => env('REDIS_PORT', 6380),
            'database' => env('REDIS_DB', 0),
        ],
    ],
];
