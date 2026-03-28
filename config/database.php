<?php

return [

    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
    ],

    'redis' => [
        'client' => 'predis',

        'default' => [
            'host' => env('REDIS_HOST', '100.68.122.24'),
            'port' => env('REDIS_PORT', 6380),
            'database' => env('REDIS_DB', 0),
        ],

        'cache' => [
            'host' => env('REDIS_HOST', '100.68.122.24'),
            'port' => env('REDIS_PORT', 6380),
            'database' => env('REDIS_CACHE_DB', 1),
        ],
    ],

];
