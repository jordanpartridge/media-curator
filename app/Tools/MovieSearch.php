<?php

namespace App\Tools;

use App\Services\RadarrService;

class MovieSearch extends CriterionTool
{
    public function __construct(
        private readonly RadarrService $radarr,
    ) {}

    public function name(): string
    {
        return 'movie_search';
    }

    public function description(): string
    {
        return 'Search for movies by title or description. Returns matching films with title, year, TMDB ID, overview, and genres.';
    }

    public function execute(array $parameters): array
    {
        $query = $parameters['query'] ?? '';

        if ($query === '') {
            return [];
        }

        $results = $this->radarr->lookup($query);

        return array_map(fn (array $movie) => [
            'title' => $movie['title'] ?? '',
            'year' => $movie['year'] ?? null,
            'tmdb_id' => $movie['tmdbId'] ?? null,
            'overview' => $movie['overview'] ?? '',
            'genres' => $movie['genres'] ?? [],
        ], array_slice($results, 0, 10));
    }
}
