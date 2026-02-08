<?php

return [
    'radarr' => [
        'url' => env('RADARR_URL', 'http://100.103.253.53:7878'),
        'api_key' => env('RADARR_API_KEY'),
    ],
    'sonarr' => [
        'url' => env('SONARR_URL', 'http://100.96.59.9:8989'),
        'api_key' => env('SONARR_API_KEY'),
    ],
    'jellyfin' => [
        'url' => env('JELLYFIN_URL', 'http://100.68.122.24:8096'),
        'api_key' => env('JELLYFIN_API_KEY'),
    ],
    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://100.68.122.24:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:3b'),
    ],
];
