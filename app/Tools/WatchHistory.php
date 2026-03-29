<?php

namespace App\Tools;

use App\Services\JellyfinService;

class WatchHistory extends CriterionTool
{
    public function __construct(
        private readonly JellyfinService $jellyfin,
    ) {}

    public function name(): string
    {
        return 'watch_history';
    }

    public function description(): string
    {
        return 'Get recently watched movies within a given number of days. Shows titles and when they were watched.';
    }

    public function execute(array $parameters): array
    {
        $days = $parameters['days'] ?? 7;

        $watched = $this->jellyfin->getWatchHistory('Movie', (int) $days);

        return array_map(fn (array $item) => [
            'title' => $item['Name'] ?? '',
            'watched_at' => $item['UserData']['LastPlayedDate'] ?? null,
        ], $watched);
    }
}
