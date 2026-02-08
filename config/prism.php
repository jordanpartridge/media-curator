<?php

return [
    'prism_server' => [
        'enabled' => false,
    ],
    'providers' => [
        'ollama' => [
            'url' => env('OLLAMA_URL', 'http://100.68.122.24:11434'),
        ],
    ],
];
