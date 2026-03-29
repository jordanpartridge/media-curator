<?php

namespace App\Tools;

use App\Services\JellyfinService;
use App\Services\RadarrService;

class LibraryQuery extends CriterionTool
{
    public function __construct(
        private readonly RadarrService $radarr,
        private readonly JellyfinService $jellyfin,
    ) {}

    public function name(): string
    {
        return 'library_query';
    }

    public function description(): string
    {
        return 'Query the movie library. Combines Radarr library data with Jellyfin watch history to show movies with play count, last watched date, and date added.';
    }

    public function execute(array $parameters): array
    {
        $jellyfinItems = $this->jellyfin->getItems();
        $radarrMovies = $this->radarr->getMovies();

        $watchData = [];
        foreach ($jellyfinItems as $item) {
            $name = $item['Name'] ?? '';
            $watchData[$name] = [
                'play_count' => $item['UserData']['PlayCount'] ?? 0,
                'last_watched' => $item['UserData']['LastPlayedDate'] ?? null,
            ];
        }

        return array_map(function (array $movie) use ($watchData) {
            $title = $movie['title'] ?? '';
            $stats = $watchData[$title] ?? ['play_count' => 0, 'last_watched' => null];

            return [
                'title' => $title,
                'year' => $movie['year'] ?? null,
                'date_added' => $movie['added'] ?? null,
                'play_count' => $stats['play_count'],
                'last_watched' => $stats['last_watched'],
            ];
        }, $radarrMovies);
    }
}
