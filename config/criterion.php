<?php

return [

    'version' => env('CRITERION_VERSION', '1.0.0'),

    'qdrant' => [
        'host' => env('QDRANT_HOST', 'localhost'),
        'port' => (int) env('QDRANT_PORT', 6333),
        'collection' => env('QDRANT_COLLECTION', 'criterion_memory'),
        'dimension' => (int) env('QDRANT_DIMENSION', 1536),
    ],

    'slack' => [
        'bot_token' => env('SLACK_BOT_TOKEN'),
        'channel' => env('SLACK_CHANNEL', '#media-curator'),
    ],

    'memory' => [
        'max_history' => (int) env('CRITERION_MAX_HISTORY', 20),
        'ttl' => (int) env('CRITERION_MEMORY_TTL', 86400),
    ],

];
