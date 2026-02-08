<?php

return [
    'name' => 'Media Curator',
    'version' => app('git.version'),
    'env' => 'development',

    'providers' => [
        App\Providers\AppServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
    ],
];
